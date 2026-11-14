<?php

namespace Tests\Feature\MarketPrices;

use App\Models\MarketPrice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** F11 slice 2: vegetable and fish prices change during the day, other products have one price per day, tomorrow's estimate, and units. */
class SessionsAndEstimatesTest extends TestCase
{
    use MarketPriceHelpers, RefreshDatabase;

    public function test_one_contributor_can_record_morning_afternoon_and_evening_prices_on_one_day(): void
    {
        $contributor = $this->contributor();

        foreach (['morning' => '30', 'afternoon' => '32.50', 'evening' => '28'] as $session => $price) {
            $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm([
                'item_code' => 'tomato', 'price_session' => $session, 'unit_price' => $price,
            ]))->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['morning' => '30.00', 'afternoon' => '32.50', 'evening' => '28.00'],
            MarketPrice::orderBy('id')->pluck('unit_price', 'price_session')->all(),
        );
    }

    public function test_products_other_than_vegetables_and_fish_have_one_price_per_day(): void
    {
        $contributor = $this->contributor();

        foreach (['rice_coarse', 'beef', 'chicken_broiler', 'egg_chicken', 'milk_cow', 'oil_mustard'] as $item) {
            // A session sent for these products is ignored: they are whole-day prices.
            $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm([
                'item_code' => $item, 'price_session' => 'evening', 'unit_price' => '100',
            ]))->assertSessionHasNoErrors();
        }
        $this->assertSame(['all_day'], MarketPrice::distinct()->pluck('price_session')->all());

        // A second, different rice price for the same day is refused: rice has one price per day.
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm([
            'item_code' => 'rice_coarse', 'price_session' => 'morning', 'unit_price' => '58',
        ]))->assertSessionHasErrors('unit_price');
        $this->assertSame(6, MarketPrice::count());

        $this->actingAs($contributor)->get(route('contributor.prices.index'))->assertSee('Whole day');
    }

    public function test_a_daily_product_does_not_need_a_session_in_the_form(): void
    {
        $form = $this->validForm(['item_code' => 'lentil']);
        unset($form['price_session']);

        $this->actingAs($this->contributor())->post(route('contributor.prices.store'), $form)->assertSessionHasNoErrors();

        $this->assertSame('all_day', MarketPrice::sole()->price_session);
    }

    public function test_fish_has_several_prices_a_day_like_vegetables(): void
    {
        $contributor = $this->contributor();

        foreach (['morning' => '340', 'evening' => '300'] as $session => $price) {
            $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm([
                'item_code' => 'fish_rui', 'price_session' => $session, 'unit_price' => $price,
            ]))->assertSessionHasNoErrors();
        }

        $this->assertSame(['morning', 'evening'], MarketPrice::orderBy('id')->pluck('price_session')->all());
    }

    public function test_a_daily_products_actual_price_and_its_estimate_for_the_same_day_can_both_exist(): void
    {
        $contributor = $this->contributor();
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['item_code' => 'beef', 'entry_kind' => 'estimate', 'unit_price' => '790']))
            ->assertSessionHasNoErrors();

        $this->travelTo(now(MarketPrice::TIMEZONE)->addDay()->setTime(10, 0));
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['item_code' => 'beef', 'unit_price' => '780']))
            ->assertSessionHasNoErrors();

        // Both are "whole day" for the same date; is_estimate keeps them apart.
        $this->assertSame([true, false], MarketPrice::orderBy('id')->pluck('is_estimate')->all());
        $this->assertSame(['all_day'], MarketPrice::distinct()->pluck('price_session')->all());
    }

    public function test_an_actual_price_needs_a_known_session(): void
    {
        $contributor = $this->contributor();

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_session' => '']))
            ->assertSessionHasErrors('price_session');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_session' => 'midnight']))
            ->assertSessionHasErrors('price_session');
        // "all_day" is reserved for estimates; an actual price cannot claim it.
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_session' => 'all_day']))
            ->assertSessionHasErrors('price_session');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['entry_kind' => 'guess']))
            ->assertSessionHasErrors('entry_kind');

        $this->assertSame(0, MarketPrice::count());
    }

    public function test_an_estimate_is_always_for_tomorrow_whatever_the_form_says(): void
    {
        $contributor = $this->contributor();

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm([
            'entry_kind' => 'estimate',
            'price_date' => $this->daysFromToday(-30), // ignored: an estimate is for tomorrow
            'price_session' => 'evening',              // ignored: an estimate covers the whole day
            'unit_price' => '38',
        ]))->assertSessionHasNoErrors();

        $estimate = MarketPrice::sole();
        $this->assertTrue($estimate->is_estimate);
        $this->assertSame(MarketPrice::tomorrow(), $estimate->price_date->toDateString());
        $this->assertSame('all_day', $estimate->price_session);
        $this->assertSame('38.00', $estimate->unit_price);

        $this->actingAs($contributor)->get(route('contributor.prices.index'))->assertSee('Estimate for this day');
    }

    public function test_an_estimate_does_not_need_a_date_or_session_in_the_form(): void
    {
        $form = $this->validForm(['entry_kind' => 'estimate']);
        unset($form['price_date'], $form['price_session']);

        $this->actingAs($this->contributor())->post(route('contributor.prices.store'), $form)->assertSessionHasNoErrors();

        $this->assertTrue(MarketPrice::sole()->is_estimate);
    }

    public function test_one_estimate_per_item_and_the_actual_price_tomorrow_is_a_separate_row(): void
    {
        $contributor = $this->contributor();
        $estimate = $this->validForm(['entry_kind' => 'estimate', 'unit_price' => '38']);

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $estimate)->assertSessionHasNoErrors();
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $estimate)->assertSessionHasNoErrors(); // resent: nothing added
        $this->actingAs($contributor)->post(route('contributor.prices.store'), ['unit_price' => '45'] + $estimate)
            ->assertSessionHasErrors('unit_price');
        $this->assertSame(1, MarketPrice::count());

        // Tomorrow comes: the actual morning price is recorded next to the estimate, which stays as history.
        $this->travelTo(now(MarketPrice::TIMEZONE)->addDay()->setTime(9, 0));
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['unit_price' => '36']))
            ->assertSessionHasNoErrors();

        $rows = MarketPrice::orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]->price_date->toDateString(), $rows[1]->price_date->toDateString());
        $this->assertSame([true, false], $rows->pluck('is_estimate')->all());
    }

    public function test_fish_prices_can_be_recorded(): void
    {
        $this->actingAs($this->contributor())->post(route('contributor.prices.store'), $this->validForm([
            'item_code' => 'fish_hilsa', 'price_type' => 'wholesale', 'price_session' => 'evening', 'unit_price' => '1450',
        ]))->assertSessionHasNoErrors();

        $price = MarketPrice::sole();
        $this->assertSame('fish_hilsa', $price->item_code);
        $this->assertSame('Hilsa (fish)', $price->itemName());
    }

    public function test_milk_and_oil_are_priced_per_litre_and_eggs_per_dozen(): void
    {
        $contributor = $this->contributor();

        foreach (['milk_cow' => '90', 'oil_mustard' => '260', 'egg_chicken' => '150', 'beef' => '780', 'chicken_broiler' => '190'] as $item => $price) {
            $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['item_code' => $item, 'unit_price' => $price]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['milk_cow' => 'litre', 'oil_mustard' => 'litre', 'egg_chicken' => 'dozen', 'beef' => 'kg', 'chicken_broiler' => 'kg'],
            MarketPrice::orderBy('id')->get()->mapWithKeys(fn (MarketPrice $p) => [$p->item_code => $p->unit()])->all(),
        );
        $this->actingAs($contributor)->get(route('contributor.prices.index'))
            ->assertSee('Cow milk')->assertSee('/litre', false)->assertSee('/dozen', false);
        $this->actingAs($contributor)->get(route('contributor.prices.create'))
            ->assertSee('Cow milk (per litre)')->assertSee('Chicken eggs (per dozen)')->assertSee('Beef (per kg)');
    }

    /** The product list in MarketPrice::ITEMS and the database CHECK must match exactly. */
    public function test_every_product_in_the_model_is_accepted_by_the_database(): void
    {
        $base = MarketPrice::factory()->create();

        foreach (MarketPrice::itemCodes() as $i => $item) {
            DB::table('market_prices')->insert([
                'market_id' => $base->market_id, 'contributor_id' => $base->contributor_id, 'item_code' => $item,
                'price_type' => 'retail', 'unit_price' => '10', 'price_date' => $this->daysFromToday(-1 - $i),
                'price_session' => MarketPrice::changesDuringDay($item) ? 'morning' : 'all_day', 'is_estimate' => false, 'version' => 1,
            ]);
        }

        $this->assertSame(count(MarketPrice::ITEMS) + 1, MarketPrice::count());
        $this->assertSame(count(MarketPrice::ITEMS), count(MarketPrice::itemNames()), 'every product has a name');
    }

    public function test_the_database_enforces_the_session_rules(): void
    {
        $actual = MarketPrice::factory()->create();
        $estimate = MarketPrice::factory()->estimate()->create();
        $daily = MarketPrice::factory()->daily()->create();

        foreach ([
            [$actual->id, ['price_session' => 'all_day']],   // a vegetable (potato) cannot have a whole-day price
            [$daily->id, ['price_session' => 'morning']],    // rice cannot have a session
            [$actual->id, ['is_estimate' => true]],          // an estimate with a morning session
            [$estimate->id, ['price_session' => 'evening']],
            [$actual->id, ['price_session' => 'night']],
            [$actual->id, ['item_code' => 'fish_shark']],
            [$actual->id, ['unit_price' => '0']],
        ] as [$id, $change]) {
            try {
                DB::table('market_prices')->where('id', $id)->update($change);
                $this->fail('The database accepted '.json_encode($change));
            } catch (QueryException $e) {
                $this->assertStringContainsString('check constraint', strtolower($e->getMessage()));
            }
        }
    }
}
