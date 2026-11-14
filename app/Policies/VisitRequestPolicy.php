<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\VisitRequest;

class VisitRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(Role::FARMER);
    }

    public function view(User $user, VisitRequest $visitRequest): bool
    {
        return $user->id === $visitRequest->farmer_id
            || $user->id === $visitRequest->veterinarianProfile?->user_id;
    }

    public function respond(User $user, VisitRequest $visitRequest): bool
    {
        return $user->hasRole(Role::VETERINARIAN)
            && $user->id === $visitRequest->veterinarianProfile?->user_id
            && $visitRequest->status === VisitRequest::STATUS_PENDING;
    }

    public function cancel(User $user, VisitRequest $visitRequest): bool
    {
        return $user->hasRole(Role::FARMER)
            && $user->id === $visitRequest->farmer_id
            && $visitRequest->status === VisitRequest::STATUS_PENDING;
    }
}
