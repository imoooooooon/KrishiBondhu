<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The EN / বাং switch in the header (route preferences.locale, groundwork for F24). */
class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_are_english_by_default(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Marketplace');
    }

    public function test_guest_can_switch_to_bangla_and_back(): void
    {
        $this->from(route('market-prices.index'))
            ->post(route('preferences.locale'), ['locale' => 'bn'])
            ->assertRedirect(route('market-prices.index'))
            ->assertSessionHas('locale', 'bn');

        $this->get(route('home'))
            ->assertSee('<html lang="bn"', false)
            ->assertSee('ফসলের বাজার')
            ->assertSee('ন্যায্য দামে আপনার ফসল বিক্রি করুন।');

        $this->post(route('preferences.locale'), ['locale' => 'en']);

        $this->get(route('home'))->assertSee('<html lang="en"', false);
    }

    public function test_unsupported_language_is_rejected(): void
    {
        $this->post(route('preferences.locale'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
        $this->post(route('preferences.locale'), [])->assertSessionHasErrors('locale');

        $this->get(route('home'))->assertSee('<html lang="en"', false);
    }

    public function test_signed_in_users_choice_is_saved_on_their_account(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->post(route('preferences.locale'), ['locale' => 'bn']);

        $this->assertSame('bn', $user->fresh()->locale);
    }

    public function test_saved_language_applies_in_a_new_session(): void
    {
        $user = User::factory()->create(['locale' => 'bn']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee('<html lang="bn"', false)
            ->assertSee('লগ আউট');
    }

    public function test_language_is_kept_after_logging_out(): void
    {
        $user = User::factory()->create(['locale' => 'bn']);

        $this->actingAs($user)->post(route('logout'));

        $this->get(route('home'))->assertSee('<html lang="bn"', false);
    }
}
