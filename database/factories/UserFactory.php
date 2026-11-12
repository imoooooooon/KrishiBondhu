<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test users. Example: User::factory()->withRoles(Role::FARMER, Role::BUYER)->create();
 * Every factory user has the password "password".
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '01'.fake()->numberBetween(3, 9).fake()->unique()->numerify('########'),
            'email' => null,
            'password' => static::$password ??= 'password',
            'locale' => 'en',
        ];
    }

    public function withRoles(string ...$slugs): static
    {
        return $this->afterCreating(function (User $user) use ($slugs) {
            $user->roles()->attach(Role::whereIn('slug', $slugs)->pluck('id'));
        });
    }
}
