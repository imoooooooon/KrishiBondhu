<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;

class ReminderPolicy
{
    public function complete(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id && ! $reminder->isDone();
    }
}
