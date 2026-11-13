<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(__('Mobile number'));
    }

    public function test_user_can_log_in_with_mobile_number_and_password(): void
    {
        $user = User::factory()->create(['phone' => '01712345678']);

        $this->post(route('login.store'), ['phone' => '01712345678', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_number_typed_with_country_code_still_logs_in(): void
    {
        $user = User::factory()->create(['phone' => '01712345678']);

        $this->post(route('login.store'), ['phone' => '+880 1712 345678', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_or_unknown_number_is_rejected(): void
    {
        User::factory()->create(['phone' => '01712345678']);

        $this->post(route('login.store'), ['phone' => '01712345678', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('phone');
        $this->post(route('login.store'), ['phone' => '01999999999', 'password' => 'password'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_login_is_blocked_after_too_many_failed_attempts(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['phone' => '01712345678']);

        for ($i = 0; $i < LoginRequest::MAX_ATTEMPTS; $i++) {
            $this->post(route('login.store'), ['phone' => '01712345678', 'password' => 'wrong-password']);
        }

        // Even the correct password is refused until the limit expires.
        $this->post(route('login.store'), ['phone' => '01712345678', 'password' => 'password'])
            ->assertSessionHasErrors(['phone' => __('auth.throttle', ['seconds' => 60])]);
        $this->assertGuest();

        // After a minute the correct password works again.
        $this->travel(61)->seconds();
        $this->post(route('login.store'), ['phone' => '01712345678', 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_dashboard_requires_login_and_shows_the_users_roles(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->withRoles(Role::FARMER, Role::VETERINARIAN)->create(['name' => 'Karim Mia']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Karim Mia')
            ->assertSee('Farmer')
            ->assertSee('Veterinarian')
            ->assertDontSee('Administrator');
    }

    public function test_user_is_sent_back_to_the_page_they_wanted_after_logging_in(): void
    {
        User::factory()->create(['phone' => '01712345678']);

        $this->get(route('dashboard'));  // stores the intended URL

        $this->post(route('login.store'), ['phone' => '01712345678', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
