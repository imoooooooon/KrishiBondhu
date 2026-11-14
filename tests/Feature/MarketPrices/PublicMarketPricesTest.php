<?php

namespace Tests\Feature\MarketPrices;

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F11 slice 3: the public price page shows, per market + product + price type, today's latest price, yesterday's
 * price, the change, tomorrow's estimate and the last update. Anyone may read it; history stays visible.
 */
class PublicMarketPricesTest extends TestCase
{
    use MarketPriceHelpers, RefreshDatabase;

    private function price(array $attributes, ?User $contributor = null): MarketPrice
    {
        $factory = MarketPrice::factory()->for($this->kawranBazar());
        if ($contributor) {
            $factory = $factory->for($contributor, 'contributor');
        }

        return $factory->create($attributes);
    }

    public function test_anyone_can_read_prices_and_contributors_are_never_named(): void
    {
        $contributor = User::factory()->withRoles(Role::PRICE_CONTRIBUTOR)->create(['name' => 'Secret Contributor']);
        $this->price(['unit_price' => '35.50'], $contributor);

        $this->get(route('market-prices.index'))->assertOk()->assertSee('Potato')->assertSee('35.50')->assertDontSee('Secret Contributor');
        $this->actingAs($this->userWith(Role::BUYER))->get(route('market-prices.index'))->assertOk()->assertDontSee('Record a price');
        $this->actingAs($contributor)->get(route('market-prices.index'))->assertSee(route('contributor.prices.create'));
    }

    public function test_the_empty_page_explains_where_prices_come_from(): void
    {
        $this->get(route('market-prices.index'))->assertOk()->assertSee('No market prices yet');
    }

    public function test_today_shows_the_latest_session_and_yesterday_and_the_exact_change(): void
    {
        $this->price(['item_code' => 'tomato', 'price_date' => $this->daysFromToday(-1), 'price_session' => 'evening', 'unit_price' => '40.00']);
        $this->price(['item_code' => 'tomato', 'price_date' => $this->daysFromToday(-1), 'price_session' => 'morning', 'unit_price' => '44.00']);
        $this->price(['item_code' => 'tomato', 'price_session' => 'morning', 'unit_price' => '45.00']);
        $this->price(['item_code' => 'tomato', 'price_session' => 'evening', 'unit_price' => '42.50']);

        $board = $this->get(route('market-prices.index'))->assertOk()->viewData('board');
        $row = $board->first();

        $this->assertSame(1, $board->total(), 'one row per market + product + type');
        $this->assertSame('42.50', $row['today']->unit_price);       // evening beats morning today
        $this->assertSame('evening', $row['today']->price_session);
        $this->assertSame('40.00', $row['yesterday']->unit_price);   // yesterday's latest session
        $this->assertSame('+2.50', $row['change']);
        $this->assertSame('+6.3', $row['percent']);                   // 2.50 / 40 = 6.25 % -> 6.3 (half up)
        $this->assertSame('up', $row['direction']);
    }

    public function test_a_price_drop_is_shown_with_a_minus_sign(): void
    {
        $this->price(['item_code' => 'rice_coarse', 'price_session' => 'all_day', 'price_date' => $this->daysFromToday(-1), 'unit_price' => '60.00']);
        $this->price(['item_code' => 'rice_coarse', 'price_session' => 'all_day', 'unit_price' => '58.00']);

        $row = $this->get(route('market-prices.index'))->viewData('board')->first();

        $this->assertSame('-2.00', $row['change']);
        $this->assertSame('-3.3', $row['percent']);
        $this->assertSame('down', $row['direction']);
        $this->get(route('market-prices.index'))->assertSee('৳ -2.00 (-3.3%)', false);
    }

    public function test_an_old_price_is_never_shown_as_todays(): void
    {
        $this->price(['item_code' => 'onion', 'price_date' => $this->daysFromToday(-3), 'unit_price' => '80.00']);

        $row = $this->get(route('market-prices.index'))->assertSee('No price yet today')->viewData('board')->first();

        $this->assertNull($row['today']);
        $this->assertNull($row['yesterday']);
        $this->assertNull($row['change']);
        $this->assertSame($this->daysFromToday(-3), $row['latest']->price_date->toDateString()); // shown as "last updated"
    }

    public function test_tomorrows_estimate_is_shown_separately_and_never_as_an_actual_price(): void
    {
        $this->price(['unit_price' => '35.00']);
        MarketPrice::factory()->estimate()->for($this->kawranBazar())->create(['unit_price' => '38.00']);

        $response = $this->get(route('market-prices.index'))->assertSee('Estimate, not a guarantee');
        $row = $response->viewData('board')->first();

        $this->assertSame('35.00', $row['today']->unit_price);
        $this->assertSame('38.00', $row['estimate']->unit_price);

        // An estimate alone (no actual price yet) does not create a row on the board.
        DB::table('market_prices')->where('is_estimate', false)->delete();
        $this->get(route('market-prices.index'))->assertSee('No market prices yet');
    }

    public function test_retail_and_wholesale_and_markets_are_separate_rows(): void
    {
        $this->price(['price_type' => 'retail', 'unit_price' => '35.00']);
        $this->price(['price_type' => 'wholesale', 'unit_price' => '28.00']);
        MarketPrice::factory()->for(Market::where('name', 'Mohasthan Hat')->sole())->create(['unit_price' => '25.00']);

        $this->assertSame(3, $this->get(route('market-prices.index'))->viewData('board')->total());
    }

    public function test_when_two_contributors_report_the_same_time_the_latest_entry_is_shown(): void
    {
        $this->price(['unit_price' => '35.00'], $this->contributor());
        $this->price(['unit_price' => '36.00'], $this->contributor());

        $this->assertSame('36.00', $this->get(route('market-prices.index'))->viewData('board')->first()['today']->unit_price);
    }

    public function test_units_are_shown_with_every_price(): void
    {
        $this->price(['item_code' => 'milk_cow', 'price_session' => 'all_day', 'unit_price' => '90.00']);
        $this->price(['item_code' => 'egg_chicken', 'price_session' => 'all_day', 'unit_price' => '150.00']);

        $this->get(route('market-prices.index'))->assertSee('Cow milk')->assertSee('/litre', false)->assertSee('/dozen', false);
    }

    public function test_filters_by_group_product_market_and_type(): void
    {
        $this->price(['item_code' => 'potato', 'unit_price' => '35.00']);
        $this->price(['item_code' => 'fish_rui', 'price_type' => 'wholesale', 'unit_price' => '300.00']);
        $this->price(['item_code' => 'beef', 'price_session' => 'all_day', 'unit_price' => '780.00']);
        MarketPrice::factory()->for(Market::where('name', 'Mohasthan Hat')->sole())->create(['item_code' => 'onion']);

        $codes = fn (array $query) => $this->get(route('market-prices.index', $query))->viewData('board')
            ->getCollection()->map(fn ($row) => $row['latest']->item_code)->sort()->values()->all();

        $this->assertSame(['fish_rui'], $codes(['group' => 'fish']));
        $this->assertSame(['beef'], $codes(['item' => 'beef']));
        $this->assertSame(['onion'], $codes(['market' => Market::where('name', 'Mohasthan Hat')->sole()->id]));
        $this->assertSame(['fish_rui'], $codes(['type' => 'wholesale']));
        $this->assertSame(['onion', 'potato'], $codes(['group' => 'vegetable']));
    }

    public function test_invalid_filters_return_to_the_page_with_an_error(): void
    {
        foreach ([['group' => 'weapons'], ['item' => 'gold'], ['market' => 999999], ['type' => 'auction']] as $filters) {
            $this->get(route('market-prices.index', $filters))->assertRedirect(route('market-prices.index'))->assertSessionHasErrors();
        }
    }

    public function test_recent_entries_keep_older_prices_visible_for_one_product_at_one_market(): void
    {
        foreach ([-5, -2, -1] as $day) {
            $this->price(['price_date' => $this->daysFromToday($day), 'unit_price' => (string) (30 - $day)]);
        }
        $this->price(['unit_price' => '30.00', 'note' => '<script>alert(1)</script>new harvest']);
        MarketPrice::factory()->estimate()->for($this->kawranBazar())->create(['unit_price' => '29.00']);

        $response = $this->get(route('market-prices.index', ['item' => 'potato', 'market' => $this->kawranBazar()->id]))
            ->assertOk()
            ->assertSee('Recent entries: Potato at Kawran Bazar')
            ->assertSee('35.00') // five days ago: history is still there
            ->assertSee('Estimate')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;new harvest', false);

        $this->assertCount(5, $response->viewData('recent'));
        $this->assertSame('29.00', $response->viewData('recent')->first()->unit_price, 'newest first: tomorrow’s estimate');
    }

    public function test_the_board_uses_the_same_number_of_queries_for_1_or_20_rows(): void
    {
        $this->price(['item_code' => 'potato']);
        $one = $this->countQueries(fn () => $this->get(route('market-prices.index'))->assertOk());

        foreach (array_slice(MarketPrice::itemCodes(), 0, 19) as $item) {
            if ($item === 'potato') {
                continue;
            }
            $session = MarketPrice::changesDuringDay($item) ? 'morning' : 'all_day';
            $this->price(['item_code' => $item, 'price_session' => $session, 'price_date' => $this->daysFromToday(-1)]);
            $this->price(['item_code' => $item, 'price_session' => $session]);
        }
        $many = $this->countQueries(fn () => $this->get(route('market-prices.index'))->assertOk());

        $this->assertSame($one, $many, 'no query per row (N+1)');
    }

    public function test_bangla_page_shows_bangla_digits_and_names(): void
    {
        $this->price(['price_date' => $this->daysFromToday(-1), 'unit_price' => '40.00']);
        $this->price(['unit_price' => '42.50']);

        $this->withSession(['locale' => 'bn'])->get(route('market-prices.index'))
            ->assertOk()
            ->assertSee('আলু')
            ->assertSee('কারওয়ান বাজার')
            ->assertSee('৪২.৫০')
            ->assertSee('+২.৫০');
    }

    public function test_compare_trends_and_market_pages_stay_planned(): void
    {
        foreach (['market-prices.compare', 'market-prices.trends', 'markets.index', 'markets.map'] as $name) {
            $this->get(route($name))->assertOk()->assertSee($name);
        }
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
