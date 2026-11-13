<?php

namespace App\Policies;

use App\Models\Cattle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may see and change a cattle record (F6). Cattle records are private: there is no public page.
 *
 * - Herd list and registering: the Farmer role, otherwise 403.
 * - One animal (profile, photo, health history, edit, archive, restore): only its owner. Another farmer
 *   gets 404, so the response does not even confirm that the animal exists.
 * - No automatic access for vets, agents or administrators; F7 (sharing with agents) will add its own rule.
 */
class CattlePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(Role::FARMER);
    }

    public function view(User $user, Cattle $cattle): Response
    {
        return $this->ownerOnly($user, $cattle);
    }

    /** Edit the profile, add or correct health entries, archive and restore. Archived state is checked by the services. */
    public function update(User $user, Cattle $cattle): Response
    {
        return $this->ownerOnly($user, $cattle);
    }

    private function ownerOnly(User $user, Cattle $cattle): Response
    {
        if (! $user->hasRole(Role::FARMER)) {
            return Response::deny(); // 403: this account has no cattle workspace
        }

        return $user->id === $cattle->farmer_id ? Response::allow() : Response::denyAsNotFound();
    }
}
