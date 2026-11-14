<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\VeterinarianProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VetProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::VETERINARIAN), 403);

        $profile = $user->veterinarianProfile ?? new VeterinarianProfile;

        return view('vet.profile.edit', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->veterinarianProfile) {
            Gate::authorize('update', $user->veterinarianProfile);
        } else {
            Gate::authorize('create', VeterinarianProfile::class);
        }

        $validated = $request->validate([
            'district' => ['required', 'string', 'max:60'],
            'upazila' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'treats_cattle' => ['nullable', 'boolean'],
            'treats_goats' => ['nullable', 'boolean'],
            'treats_poultry' => ['nullable', 'boolean'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_listed' => ['nullable', 'boolean'],
        ]);

        $validated['treats_cattle'] = $request->boolean('treats_cattle');
        $validated['treats_goats'] = $request->boolean('treats_goats');
        $validated['treats_poultry'] = $request->boolean('treats_poultry');
        $validated['is_listed'] = $request->boolean('is_listed');

        $user->veterinarianProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return redirect()
            ->route('vet.profile.edit')
            ->with('status', __('Veterinarian profile saved successfully.'));
    }
}
