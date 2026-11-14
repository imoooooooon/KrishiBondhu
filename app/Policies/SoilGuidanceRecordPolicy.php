<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\SoilGuidanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may use soil guidance (F15). Laravel finds this class by its name
 * (SoilGuidanceRecord -> SoilGuidanceRecordPolicy). Every route asks it, so hiding a button is never the only check.
 *
 * - The form, the history page and submitting: the Farmer role (other roles may be held too), otherwise 403.
 * - One request (result page, photo, retry): only the farmer who made it. Another farmer gets 404, so the
 *   response does not even confirm that the request exists.
 * - Administrators get no automatic access: the project has no admin rule for private farmer records yet.
 *
 * WHEN a retry is allowed (failed, attempts left, cooldown) is a rule of the record itself:
 * see SoilGuidanceRecord::canRetry().
 */
class SoilGuidanceRecordPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(Role::FARMER);
    }

    /** The result page and the private photo. */
    public function view(User $user, SoilGuidanceRecord $record): Response
    {
        return $this->ownerOnly($user, $record);
    }

    public function retry(User $user, SoilGuidanceRecord $record): Response
    {
        return $this->ownerOnly($user, $record);
    }

    private function ownerOnly(User $user, SoilGuidanceRecord $record): Response
    {
        if (! $user->hasRole(Role::FARMER)) {
            return Response::deny(); // 403: this account has no soil guidance workspace
        }

        return $user->id === $record->farmer_id ? Response::allow() : Response::denyAsNotFound();
    }
}
