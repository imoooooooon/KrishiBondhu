<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function validForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rahima Begum',
            'phone' => '01712345678',
            'email' => '',
            'password' => 'paddy-field-9',
            'password_confirmation' => 'paddy-field-9',
            'roles' => [Role::FARMER, Role::BUYER],
        ], $overrides);
    }

    public function test_sign_up_page_lists_every_role_except_administrator(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('value="farmer"', false)
            ->assertSee('value="seller"', false)
            ->assertDontSee('value="admin"', false);
    }

    public function test_person_can_register_with_a_mobile_number_and_several_roles(): void
    {
        $response = $this->post(route('register.store'), $this->validForm());

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('phone', '01712345678')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertTrue(Hash::check('paddy-field-9', $user->password));
        $this->assertTrue($user->hasRole(Role::FARMER));
        $this->assertTrue($user->hasRole(Role::BUYER));
        $this->assertFalse($user->hasRole(Role::SELLER));
    }

    public function test_country_code_spaces_and_bangla_digits_are_normalised(): void
    {
        $this->post(route('register.store'), $this->validForm(['phone' => '+880 1712-345678']));
        $this->assertDatabaseHas('users', ['phone' => '01712345678']);

        auth()->logout();

        $this->post(route('register.store'), $this->validForm(['phone' => '০১৮১২৩৪৫৬৭৮']));
        $this->assertDatabaseHas('users', ['phone' => '01812345678']);
    }

    public function test_invalid_mobile_numbers_are_rejected(): void
    {
        foreach (['0171234567', '017123456789', '01212345678', '02712345678', 'phone-number'] as $phone) {
            $this->post(route('register.store'), $this->validForm(['phone' => $phone]))
                ->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_mobile_number_and_email_must_be_unique(): void
    {
        User::factory()->create(['phone' => '01712345678', 'email' => 'rahima@example.com']);

        $this->post(route('register.store'), $this->validForm())->assertSessionHasErrors('phone');
        $this->post(route('register.store'), $this->validForm(['phone' => '01912345678', 'email' => 'Rahima@Example.com']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_at_least_one_role_is_required_and_administrator_cannot_be_chosen(): void
    {
        $this->post(route('register.store'), $this->validForm(['roles' => []]))->assertSessionHasErrors('roles');
        $this->post(route('register.store'), $this->validForm(['roles' => [Role::FARMER, Role::ADMIN]]))->assertSessionHasErrors('roles.1');
        $this->post(route('register.store'), $this->validForm(['roles' => ['astronaut']]))->assertSessionHasErrors('roles.0');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_must_be_confirmed_and_at_least_eight_characters(): void
    {
        $this->post(route('register.store'), $this->validForm(['password_confirmation' => 'different-1']))
            ->assertSessionHasErrors('password');
        $this->post(route('register.store'), $this->validForm(['password' => 'short-7', 'password_confirmation' => 'short-7']))
            ->assertSessionHasErrors('password');

        $this->post(route('register.store'), $this->validForm(['password' => 'exactly8', 'password_confirmation' => 'exactly8']))
            ->assertRedirect(route('dashboard'));
    }

    public function test_new_account_keeps_the_language_chosen_before_signing_up(): void
    {
        $this->withSession(['locale' => 'bn'])->post(route('register.store'), $this->validForm());

        $this->assertSame('bn', User::firstOrFail()->locale);
    }

    public function test_validation_errors_are_shown_in_bangla(): void
    {
        $this->withSession(['locale' => 'bn'])
            ->from(route('register'))
            ->post(route('register.store'), $this->validForm(['name' => '', 'phone' => '123']))
            ->assertSessionHasErrors([
                'name' => 'নাম দিতে হবে।',
                'phone' => '১১ সংখ্যার বাংলাদেশি মোবাইল নম্বর দিন, যেমন 01712345678।',
            ]);
    }

    public function test_signed_in_users_cannot_open_the_sign_up_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('register'))
            ->assertRedirect(route('dashboard'));
    }
}
