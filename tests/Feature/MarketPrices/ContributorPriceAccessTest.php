<?php

namespace Tests\Feature\MarketPrices;

use App\Models\MarketPrice;
use App\Models\Role;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F11: who may use the contributor workspace. Every request is sent directly, as an attacker would,
 * instead of relying on buttons being hidden.
 */
class ContributorPriceAccessTest extends TestCase
{
    use MarketPriceHelpers, RefreshDatabase;

    public function test_guests_are_sent_to_log_in_from_the_contributor_workspace(): void
    {
        foreach ([
            $this->get(route('contributor.prices.index')),
            $this->get(route('contributor.prices.create')),
            $this->post(route('contributor.prices.store'), $this->validForm()),
        ] as $response) {
            $response->assertRedirect(route('login'));
        }

        $this->assertSame(0, MarketPrice::count());
    }

    public function test_signed_in_users_without_the_contributor_role_are_forbidden(): void
    {
        // An administrator gets no automatic bypass.
        foreach ([Role::BUYER, Role::FARMER, Role::SELLER, Role::ADMIN] as $role) {
            $user = $this->userWith($role);

            $this->actingAs($user)->get(route('contributor.prices.index'))->assertForbidden();
            $this->actingAs($user)->get(route('contributor.prices.create'))->assertForbidden();
            $this->actingAs($user)->post(route('contributor.prices.store'), $this->validForm())->assertForbidden();
        }

        $this->assertSame(0, MarketPrice::count());
    }

    public function test_a_user_with_several_roles_including_contributor_can_record(): void
    {
        $user = $this->userWith(Role::FARMER, Role::PRICE_CONTRIBUTOR);

        $this->actingAs($user)->post(route('contributor.prices.store'), $this->validForm())->assertSessionHasNoErrors();

        $this->assertSame($user->id, MarketPrice::sole()->contributor_id);
    }

    public function test_my_prices_shows_only_the_contributors_own_entries(): void
    {
        $contributor = $this->contributor();
        MarketPrice::factory()->for($contributor, 'contributor')->create(['note' => 'Mine at the hat']);
        MarketPrice::factory()->create(['note' => 'Someone else’s entry']);

        $this->actingAs($contributor)->get(route('contributor.prices.index'))
            ->assertOk()
            ->assertSee('Mine at the hat')
            ->assertDontSee('Someone else’s entry');
    }

    public function test_the_empty_list_invites_the_contributor_to_record_a_price(): void
    {
        $this->actingAs($this->contributor())->get(route('contributor.prices.index'))
            ->assertOk()->assertSee('You have not recorded any prices');
    }

    public function test_price_history_has_no_delete_route(): void
    {
        $contributor = $this->contributor();
        $price = MarketPrice::factory()->for($contributor, 'contributor')->create();

        $this->actingAs($contributor)->delete('/contributor/prices/'.$price->id)->assertStatus(405);

        $this->assertFalse(app('router')->has('contributor.prices.destroy'));
        $this->assertNotNull($price->fresh());
    }

    public function test_forged_writes_are_rejected_when_csrf_protection_is_active(): void
    {
        // Laravel switches CSRF checks off in tests; this version keeps them on, like a real browser request.
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });

        $this->actingAs($this->contributor())->post(route('contributor.prices.store'), $this->validForm())->assertStatus(419);

        $this->assertSame(0, MarketPrice::count());
    }

    public function test_recording_is_rate_limited_per_account(): void
    {
        $contributor = $this->contributor();

        // Invalid (empty) forms still count: the limit applies before any validation.
        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($contributor)->post(route('contributor.prices.store'), [])->assertRedirect();
        }
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm())->assertStatus(429);

        $this->assertSame(0, MarketPrice::count());
        // Another contributor has their own limit.
        $this->actingAs($this->contributor())->post(route('contributor.prices.store'), $this->validForm())->assertSessionHasNoErrors();
    }

    public function test_contributor_text_is_shown_as_text_never_as_html(): void
    {
        $contributor = $this->contributor();
        MarketPrice::factory()->for($contributor, 'contributor')->create(['note' => '<script>alert(1)</script>grade A']);

        $this->actingAs($contributor)->get(route('contributor.prices.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;grade A', false);
    }

    public function test_the_list_uses_the_same_number_of_queries_for_1_or_15_entries(): void
    {
        $contributor = $this->contributor();
        // fresh(): a new User object each time, so its roles are not already cached from the previous request.
        MarketPrice::factory()->for($contributor, 'contributor')->create();
        $one = $this->countQueries(fn () => $this->actingAs($contributor->fresh())->get(route('contributor.prices.index'))->assertOk());

        foreach (range(1, 14) as $day) {
            MarketPrice::factory()->for($contributor, 'contributor')->create(['price_date' => $this->daysFromToday(-$day)]);
        }
        $fifteen = $this->countQueries(fn () => $this->actingAs($contributor->fresh())->get(route('contributor.prices.index'))->assertOk());

        $this->assertSame($one, $fifteen, 'no query per row (N+1)');
    }

    public function test_route_names_stay_and_the_f12_to_f14_routes_stay_planned(): void
    {
        foreach (['market-prices.compare', 'market-prices.trends', 'markets.index', 'markets.map'] as $name) {
            $this->get(route($name))->assertOk()->assertSee($name); // the "planned page" shows its route name
        }
        $this->get(route('markets.show', 1))->assertOk()->assertSee('markets.show');

        // Corrections arrive in slice 2; until then they answer "not built yet".
        $this->get(route('contributor.prices.edit', 1))->assertOk()->assertSee('contributor.prices.edit');
        $this->patch(route('contributor.prices.update', 1))->assertStatus(501);
    }

    private function countQueries(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
