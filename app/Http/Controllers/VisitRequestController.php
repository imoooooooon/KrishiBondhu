<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\VeterinarianProfile;
use App\Models\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisitRequestController extends Controller
{
    public const ANIMAL_TYPES = ['cattle', 'goat', 'poultry', 'other'];

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::FARMER), 403);

        $requests = VisitRequest::with('veterinarianProfile.user')
            ->where('farmer_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('visit-requests.index', compact('requests'));
    }

    public function show(VisitRequest $visitRequest): View
    {
        Gate::authorize('view', $visitRequest);
        $visitRequest->load(['farmer', 'veterinarianProfile.user']);

        return view('visit-requests.show', compact('visitRequest'));
    }

    public function store(Request $request, VeterinarianProfile $vet): RedirectResponse
    {
        Gate::authorize('create', VisitRequest::class);

        abort_unless($vet->is_listed, 404);

        $alreadyPending = VisitRequest::where('farmer_id', $request->user()->id)
            ->where('veterinarian_profile_id', $vet->id)
            ->where('status', VisitRequest::STATUS_PENDING)
            ->exists();

        if ($alreadyPending) {
            return back()
                ->withInput()
                ->withErrors(['veterinarian_profile_id' => __('You already have a pending visit request with this veterinarian.')]);
        }

        $validated = $request->validate([
            'animal_type' => ['required', 'string', Rule::in(self::ANIMAL_TYPES)],
            'problem' => ['required', 'string', 'max:2000'],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'farm_address' => ['required', 'string', 'max:255'],
        ]);

        $visitRequest = new VisitRequest($validated);
        $visitRequest->farmer_id = $request->user()->id;
        $visitRequest->veterinarian_profile_id = $vet->id;
        $visitRequest->save();

        return redirect()
            ->route('visit-requests.index')
            ->with('status', __('Visit request sent successfully.'));
    }

    public function cancel(VisitRequest $visitRequest): RedirectResponse
    {
        Gate::authorize('cancel', $visitRequest);

        $visitRequest->status = VisitRequest::STATUS_CANCELLED;
        $visitRequest->save();

        return redirect()
            ->route('visit-requests.index')
            ->with('status', __('Visit request cancelled.'));
    }
}
