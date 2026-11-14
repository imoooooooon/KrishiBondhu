<?php

namespace App\Http\Controllers;

use App\Http\Requests\SoilGuidance\StoreSoilGuidanceRequest;
use App\Models\SoilGuidanceRecord;
use App\Services\CropListingPhotoService;
use App\Services\SoilGuidanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * F15: soil-based fertilizer guidance. Routes: routes/modules/health.php (guidance.soil.*).
 * Spec: docs/features/F15-soil-fertilizer-guidance.md.
 *
 * Coordinates only: who may act -> SoilGuidanceRecordPolicy, valid input -> StoreSoilGuidanceRequest,
 * saving and the AI call -> SoilGuidanceService. Nothing about Gemini is in this class.
 */
class SoilGuidanceController extends Controller
{
    public function __construct(private readonly SoilGuidanceService $guidance) {}

    /** "My soil guidance": this farmer's own requests, newest first. */
    public function index(Request $request): View
    {
        Gate::authorize('create', SoilGuidanceRecord::class); // the workspace needs the Farmer role

        $records = $request->user()->soilGuidanceRecords() // starts from THIS farmer's records only
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(SoilGuidanceRecord::PER_PAGE);

        return view('guidance.soil.index', ['records' => $records]);
    }

    public function create(): View
    {
        Gate::authorize('create', SoilGuidanceRecord::class);

        return view('guidance.soil.create', ['submissionToken' => (string) Str::uuid()]);
    }

    /**
     * Save the observations, then ask the AI service straight away (bounded by a time limit).
     * Whatever happens, the farmer lands on the result page, which shows the guidance or a clear failure message.
     */
    public function store(StoreSoilGuidanceRequest $request): RedirectResponse
    {
        [$record, $created] = $this->guidance->createRecord(
            $request->user(),
            $request->observationFields(),
            $request->validated('submission_token'),
            $request->file('photo'),
            app()->getLocale(),
        );

        if ($created) {
            $this->guidance->process($record);
        }

        return redirect()->route('guidance.soil.show', $record);
    }

    public function show(SoilGuidanceRecord $record): View
    {
        Gate::authorize('view', $record);

        return view('guidance.soil.show', ['record' => $record]);
    }

    /** Try a failed request again with the SAME saved observations. POST only: it calls the AI service. */
    public function retry(SoilGuidanceRecord $record): RedirectResponse
    {
        Gate::authorize('retry', $record);

        $back = redirect()->route('guidance.soil.show', $record);

        if (! $record->canRetry()) {
            return $back->withErrors(['retry' => __('This request cannot be tried again. Make a new request instead.')]);
        }

        if (($wait = $record->retryWaitSeconds()) > 0) {
            return $back->withErrors(['retry' => trans_choice('Please wait :count second before trying again.|Please wait :count seconds before trying again.', $wait, ['count' => $wait])]);
        }

        // process() claims the record first. If another request claimed it a moment ago, nothing is sent twice.
        $this->guidance->process($record);

        return $back;
    }

    /** The private soil photo: only its owner gets it, checked on every request. */
    public function photo(SoilGuidanceRecord $record, CropListingPhotoService $photos): Response
    {
        Gate::authorize('view', $record);

        $bytes = $record->hasPhoto() ? $photos->read($record->photo_path) : null;
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }
}
