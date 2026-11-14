<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;

/**
 * Shared helpers for the demo seeders (development and the viva only). DELETE BEFORE RELEASE.
 * Every demo account uses the password "password" and a phone number starting with 017000000.
 */
trait DemoAccounts
{
    /** Find the demo account by phone, or create it. An existing account is left exactly as it is. */
    private function demoUser(string $phone, string $name, array $roles): User
    {
        $user = User::firstOrCreate(['phone' => $phone], ['name' => $name, 'password' => 'password', 'locale' => 'en']);

        if ($user->wasRecentlyCreated) {
            $user->roles()->attach(Role::whereIn('slug', $roles)->pluck('id'));
        }

        return $user;
    }

    /** Demo data must never reach a real deployment. */
    private function refuseInProduction(): bool
    {
        if (app()->isProduction()) {
            $this->command?->warn(static::class.' is for development only. Nothing was added.');

            return true;
        }

        return false;
    }
}
