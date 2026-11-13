<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cattle\ArchiveCattleRequest;
use App\Http\Requests\Cattle\StoreCattleRequest;
use App\Http\Requests\Cattle\UpdateCattleRequest;
use App\Models\Cattle;
use App\Services\CattleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * F6: digital cattle profiles. Routes: routes/modules/cattle.php (cattle.*). Spec: docs/features/F6-digital-cattle-profiles.md.
 * Coordinates only: who may act -> CattlePolicy, valid input -> Form Requests, safe writes -> CattleService.
 */
class CattleController extends Controller
{
    public const SCOPES = ['active', 'archived', 'all'];

    public function __construct(private readonly CattleService $cattle) {}

    /** "My cattle": search by KB ID, farm tag or nickname; Active / Archived / All; 12 per page. */
    public function index(Request $request): View
    {
        Gate::authorize('create', Cattle::class); // the workspace needs the Farmer role

        $scope = $request->query('scope', 'active');
        $q = $request->query('q');
        $badFilter = ! in_array($scope, self::SCOPES, true) || (! is_null($q) && (! is_string($q) || mb_strlen($q) > 80));
        if ($badFilter) {
            [$scope, $q] = ['active', null];
        }
        $q = is_string($q) ? trim($q) : '';

        $herd = $request->user()->cattle(); // every query below starts from THIS farmer's cattle only

        $cattle = (clone $herd)
            ->when($scope === 'active', fn ($query) => $query->active())
            ->when($scope === 'archived', fn ($query) => $query->archived())
            ->when($q !== '', function ($query) use ($q) {
                $id = strtoupper(preg_replace('/^KB-C-/i', '', $q));
                $prefix = addcslashes($q, '\\%_').'%'; // % and _ typed by the farmer are searched as normal characters
                // Brackets around the ORs, so they stay inside "farmer_id = this farmer".
                $query->where(fn ($match) => $match->where('public_id', $id)
                    ->orWhere('farm_tag', 'like', $prefix)
                    ->orWhere('nickname', 'like', $prefix));
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(Cattle::PER_PAGE)
            ->withQueryString();

        $archivedCount = (clone $herd)->archived()->count();

        return view('cattle.index', [
            'cattle' => $cattle,
            'scope' => $scope,
            'q' => $q,
            'badFilter' => $badFilter,
            'counts' => ['active' => (clone $herd)->count() - $archivedCount, 'archived' => $archivedCount],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Cattle::class);

        return view('cattle.create', ['submissionToken' => (string) Str::uuid()]);
    }

    public function store(StoreCattleRequest $request): RedirectResponse
    {
        [$cattle, $created] = $this->cattle->register(
            $request->user(), $request->profileData(), $request->ageData(),
            $request->validated('submission_token'), $request->file('photo'), $request->firstEntry(),
        );

        return redirect()->route('cattle.show', $cattle)->with('status', $created
            ? __('Cattle registered. Its KB ID is :id.', ['id' => $cattle->displayId()])
            : __('This animal was already registered from your earlier submission. Nothing was added twice.'));
    }

    /** Owner profile with paged health history (?health_page=N) and the archive confirmation (?confirm=archive). */
    public function show(Request $request, Cattle $cattle): View
    {
        Gate::authorize('view', $cattle);

        $entries = $cattle->healthEntries()
            ->orderByDesc('occurred_on') // newest known date first; MySQL puts "date not recorded" (NULL) last
            ->orderByDesc('id')
            ->paginate(Cattle::HISTORY_PER_PAGE, ['*'], 'health_page')
            ->withQueryString();

        return view('cattle.show', [
            'cattle' => $cattle,
            'entries' => $entries,
            'confirmArchive' => $request->query('confirm') === 'archive' && ! $cattle->isArchived(),
            'connected' => $this->connectedFeatures($cattle),
        ]);
    }

    public function edit(Cattle $cattle): View|RedirectResponse
    {
        Gate::authorize('update', $cattle);

        if ($cattle->isArchived()) {
            return redirect()->route('cattle.show', $cattle)->with('status', __('This animal is archived. Restore it before making changes.'));
        }

        return view('cattle.edit', ['cattle' => $cattle]);
    }

    /** Save the edit form, or restore an archived animal (operation=restore). */
    public function update(UpdateCattleRequest $request, Cattle $cattle): RedirectResponse
    {
        if ($request->operation() === 'restore') {
            $this->cattle->restore($cattle, $request->version());

            return redirect()->route('cattle.show', $cattle)->with('status', __('Restored. This animal is back in your active list.'));
        }

        $this->cattle->update($cattle, $request->version(), $request->profileData(), $request->ageChange(), $request->file('photo'), $request->submittedText());

        return redirect()->route('cattle.show', $cattle)->with('status', __('Profile saved.'));
    }

    /** Archive (soft): it leaves the active list; profile, photo and history stay. */
    public function destroy(ArchiveCattleRequest $request, Cattle $cattle): RedirectResponse
    {
        $this->cattle->archive($cattle, $request->version());

        return redirect()->route('cattle.index')->with('status', __('Archived. You can find it under Archived and restore it at any time.'));
    }

    /**
     * Links to the cattle features other members build (F7–F10, F18). A link is marked "Planned" while its route
     * still points at PlannedController, so the label disappears by itself once the feature is built.
     *
     * @return list<array{label: string, description: string, url: string, icon: string, planned: bool}>
     */
    private function connectedFeatures(Cattle $cattle): array
    {
        $links = [
            ['cattle.inseminations.index', [$cattle], __('Insemination records'), __('Services by an insemination agent (F7)'), 'syringe'],
            ['cattle.family-tree', [$cattle], __('Family tree'), __('Linked parents and offspring (F8)'), 'network'],
            ['cattle.pregnancies.index', [$cattle], __('Pregnancy records'), __('Pregnancy and calving (F10)'), 'calendar'],
            ['breeding-check.create', [], __('Breeding check'), __('Inbreeding warning before a service (F9)'), 'shield-check'],
            ['vets.index', [], __('Find a veterinarian'), __('Vets near you (F18)'), 'stethoscope'],
        ];

        return array_map(fn ($link) => [
            'url' => route($link[0], $link[1]),
            'label' => $link[2],
            'description' => $link[3],
            'icon' => $link[4],
            'planned' => str_contains((string) Route::getRoutes()->getByName($link[0])?->getActionName(), 'PlannedController'),
        ], $links);
    }
}
