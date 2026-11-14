<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\VeterinarianProfile;
use Illuminate\Auth\Access\Response;

class VeterinarianProfilePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, VeterinarianProfile $veterinarianProfile): Response
    {
        if ($veterinarianProfile->is_listed || ($user && $user->id === $veterinarianProfile->user_id)) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::VETERINARIAN) && $user->veterinarianProfile === null;
    }

    public function update(User $user, VeterinarianProfile $veterinarianProfile): bool
    {
        return $user->hasRole(Role::VETERINARIAN) && $user->id === $veterinarianProfile->user_id;
    }
}
