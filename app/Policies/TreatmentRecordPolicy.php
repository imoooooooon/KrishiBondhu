<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\TreatmentRecord;
use App\Models\User;

class TreatmentRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::FARMER);
    }

    public function view(User $user, TreatmentRecord $treatmentRecord): bool
    {
        return $user->hasRole(Role::FARMER) && $user->id === $treatmentRecord->farmer_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::FARMER);
    }

    public function update(User $user, TreatmentRecord $treatmentRecord): bool
    {
        return $user->hasRole(Role::FARMER) && $user->id === $treatmentRecord->farmer_id;
    }

    public function delete(User $user, TreatmentRecord $treatmentRecord): bool
    {
        return $user->hasRole(Role::FARMER) && $user->id === $treatmentRecord->farmer_id;
    }
}