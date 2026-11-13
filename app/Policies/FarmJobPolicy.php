<?php

namespace App\Policies;

use App\Models\FarmJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what with a farm job (F20). Laravel finds this class by its name (FarmJob -> FarmJobPolicy).
 * The controller and form requests ask it before every action, so hiding a button is never the only check.
 */
class FarmJobPolicy
{
    /**
     * Job details. Anyone (even a guest, so $user may be null) can open an available job.
     * A closed or past job is shown only to its owner; everyone else gets "404 Not Found".
     */
    public function view(?User $user, FarmJob $job): Response
    {
        return $job->isAvailable() || $this->owns($user, $job)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Posting jobs and the "My posted jobs" page need the Farmer role. */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::FARMER);
    }

    /** Only the farmer who posted the job can edit it. */
    public function update(User $user, FarmJob $job): bool
    {
        return $this->owns($user, $job);
    }

    /** Only the farmer who posted the job can close it. */
    public function close(User $user, FarmJob $job): bool
    {
        return $this->owns($user, $job);
    }

    private function owns(?User $user, FarmJob $job): bool
    {
        return $user !== null && $user->id === $job->farmer_id;
    }
}
