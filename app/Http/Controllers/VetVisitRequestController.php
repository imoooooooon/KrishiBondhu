<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VetVisitRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::VETERINARIAN), 403);

        $profile = $user->veterinarianProfile;
        abort_unless($profile !== null, 403);

        $requests = VisitRequest::with('farmer')
            ->where('veterinarian_profile_id', $profile->id)
            ->latest()
            ->paginate(10);

        return view('vet.visit-requests.index', compact('requests'));
    }

    public function update(Request $request, VisitRequest $visitRequest): RedirectResponse
    {
        Gate::authorize('respond', $visitRequest);

        $validated = $request->validate([
            'status' => ['required', Rule::in([VisitRequest::STATUS_ACCEPTED, VisitRequest::STATUS_DECLINED])],
            'vet_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $visitRequest->status = $validated['status'];
        $visitRequest->vet_note = $validated['vet_note'] ?? null;
        $visitRequest->responded_at = now();
        $visitRequest->save();

        return redirect()
            ->route('vet.visit-requests.index')
            ->with('status', __('Visit request status updated.'));
    }
}
