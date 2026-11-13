<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cattle\StoreCattleHealthEntryRequest;
use App\Http\Requests\Cattle\UpdateCattleHealthEntryRequest;
use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Services\CattleHealthHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** F6: add and correct health-history notes for one animal (cattle.health-history.*). */
class CattleHealthEntryController extends Controller
{
    public function __construct(private readonly CattleHealthHistoryService $history) {}

    public function create(Cattle $cattle): View|RedirectResponse
    {
        Gate::authorize('update', $cattle);
        if ($cattle->isArchived()) {
            return redirect()->route('cattle.show', $cattle)->with('status', __('This animal is archived. Restore it before making changes.'));
        }

        return view('cattle.health-history.create', ['cattle' => $cattle, 'submissionToken' => (string) Str::uuid()]);
    }

    public function store(StoreCattleHealthEntryRequest $request, Cattle $cattle): RedirectResponse
    {
        [, $created] = $this->history->create($cattle, $request->user(), $request->entryData(), $request->validated('submission_token'));

        return redirect()->route('cattle.show', $cattle)->withFragment('health-history')->with('status', $created
            ? __('Health entry saved.')
            : __('This entry was already saved from your earlier submission. Nothing was added twice.'));
    }

    /** The entry must belong to this animal; otherwise "not found", even for the same owner's other cattle. */
    public function edit(Cattle $cattle, CattleHealthEntry $entry): View|RedirectResponse
    {
        abort_if($entry->cattle_id !== $cattle->id, 404);
        Gate::authorize('update', $cattle);
        if ($cattle->isArchived()) {
            return redirect()->route('cattle.show', $cattle)->with('status', __('This animal is archived. Restore it before making changes.'));
        }

        return view('cattle.health-history.edit', ['cattle' => $cattle, 'entry' => $entry]);
    }

    public function update(UpdateCattleHealthEntryRequest $request, Cattle $cattle, CattleHealthEntry $entry): RedirectResponse
    {
        $this->history->update($cattle, $entry, $request->version(), $request->entryData(), $request->submittedText());

        return redirect()->route('cattle.show', $cattle)->withFragment('health-history')->with('status', __('Health entry corrected.'));
    }
}
