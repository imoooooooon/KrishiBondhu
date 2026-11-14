<?php

namespace App\Http\Controllers;

use App\Http\Requests\Treatments\StoreTreatmentRequest;
use App\Http\Requests\Treatments\UpdateTreatmentRequest;
use App\Models\Reminder;
use App\Models\TreatmentRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TreatmentRecord::class);

        $query = TreatmentRecord::where('farmer_id', $request->user()->id);

        if ($request->filled('category') && in_array($request->string('category')->toString(), TreatmentRecord::CATEGORIES, true)) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), TreatmentRecord::STATUSES, true)) {
            $query->where('status', $request->string('status')->toString());
        }

        $treatments = $query->latest('treated_on')->latest('id')->paginate(10)->withQueryString();

        return view('treatments.index', compact('treatments'));
    }

    public function create(): View
    {
        Gate::authorize('create', TreatmentRecord::class);

        return view('treatments.create', [
            'treatment' => new TreatmentRecord(['status' => TreatmentRecord::STATUS_ONGOING]),
        ]);
    }

    public function store(StoreTreatmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', TreatmentRecord::class);

        $treatment = new TreatmentRecord($request->validated());
        $treatment->farmer_id = $request->user()->id;
        $treatment->save();

        $this->syncReminder($treatment);

        return redirect()
            ->route('treatments.show', $treatment)
            ->with('status', __('Treatment record saved.'));
    }

    public function show(TreatmentRecord $treatment): View
    {
        Gate::authorize('view', $treatment);
        $treatment->load('reminder');

        return view('treatments.show', compact('treatment'));
    }

    public function edit(TreatmentRecord $treatment): View
    {
        Gate::authorize('update', $treatment);

        return view('treatments.edit', compact('treatment'));
    }

    public function update(UpdateTreatmentRequest $request, TreatmentRecord $treatment): RedirectResponse
    {
        Gate::authorize('update', $treatment);

        $treatment->update($request->validated());
        $this->syncReminder($treatment);

        return redirect()
            ->route('treatments.show', $treatment)
            ->with('status', __('Treatment record updated.'));
    }

    public function destroy(TreatmentRecord $treatment): RedirectResponse
    {
        Gate::authorize('delete', $treatment);

        $treatment->reminder()->whereNull('completed_at')->delete();
        $treatment->delete();

        return redirect()
            ->route('treatments.index')
            ->with('status', __('Treatment record deleted.'));
    }

    private function syncReminder(TreatmentRecord $treatment): void
    {
        if ($treatment->follow_up_date === null || $treatment->status === TreatmentRecord::STATUS_COMPLETED) {
            $treatment->reminder()->whereNull('completed_at')->delete();

            return;
        }

        $reminder = $treatment->reminder()->whereNull('completed_at')->first() ?? new Reminder;
        $reminder->user_id = $treatment->farmer_id;
        $reminder->source_type = TreatmentRecord::class;
        $reminder->source_id = $treatment->id;
        $reminder->title = __('Follow-up: :subject', ['subject' => $treatment->subject_name]);
        $reminder->notes = $treatment->problem;
        $reminder->remind_on = $treatment->follow_up_date;
        $reminder->save();
    }
}
