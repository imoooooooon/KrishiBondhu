<?php

namespace Tests\Feature\MarketPrices;

use App\Models\Market;
use App\Models\MarketPrice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** F11: recording a price: exact numbers, dates, one entry per day, forged fields and the database guards. */
class RecordMarketPriceTest extends TestCase
{
    use MarketPriceHelpers, RefreshDatabase;

    public function test_the_initial_markets_exist_after_migrating(): void
    {
        $this->assertSame(8, Market::count());
        $this->assertSame('কারওয়ান বাজার', $this->kawranBazar()->name_bn);
    }

    public function test_contributor_records_a_complete_price(): void
    {
        $contributor = $this->contributor();

        $this->actingAs($contributor)->get(route('contributor.prices.create'))->assertOk()->assertSee('Kawran Bazar');

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm())
            ->assertRedirect(route('contributor.prices.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Price recorded. Thank you: buyers and farmers can now see it.');

        $price = MarketPrice::sole();
        $this->assertSame($contributor->id, $price->contributor_id);
        $this->assertSame($this->kawranBazar()->id, $price->market_id);
        $this->assertSame('potato', $price->crop_code);
        $this->assertSame('retail', $price->price_type);
        $this->assertSame('35.50', $price->price_per_kg);
        $this->assertSame(MarketPrice::today(), $price->price_date->toDateString());
        $this->assertSame('Morning price, new harvest.', $price->note);
        $this->assertSame(1, $price->version);

        $this->actingAs($contributor)->get(route('contributor.prices.index'))
            ->assertSee('Kawran Bazar')->assertSee('Potato')->assertSee('35.50');
    }

    public function test_contributor_version_and_timestamps_cannot_be_sent_in(): void
    {
        $contributor = $this->contributor();
        $other = $this->contributor();

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm([
            'contributor_id' => $other->id,
            'version' => 99,
            'created_at' => '2001-01-01 00:00:00',
            'updated_at' => '2001-01-01 00:00:00',
            'id' => 999,
        ]))->assertSessionHasNoErrors();

        $price = MarketPrice::sole();
        $this->assertSame($contributor->id, $price->contributor_id);
        $this->assertSame(1, $price->version);
        $this->assertNotSame(999, $price->id);
        $this->assertTrue($price->created_at->isToday());
    }

    public function test_bangla_digits_are_stored_as_exact_values(): void
    {
        $date = $this->daysFromToday(-3);
        $banglaDate = strtr($date, array_combine(str_split('0123456789'), ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']));

        $this->actingAs($this->contributor())->post(route('contributor.prices.store'), $this->validForm([
            'price_per_kg' => '৩৫.৭৫',
            'price_date' => $banglaDate,
        ]))->assertSessionHasNoErrors();

        $price = MarketPrice::sole();
        $this->assertSame('35.75', $price->price_per_kg);
        $this->assertSame($date, $price->price_date->toDateString());
    }

    /** @return array<string, array{0: string}> */
    public static function invalidPrices(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-5'],
            'above maximum' => ['100000.01'],
            'too many digits' => ['1000000'],
            'comma separator' => ['1,250'],
            'currency sign' => ['৳35'],
            'scientific notation' => ['1e3'],
            'three decimals' => ['35.555'],
            'leading dot' => ['.5'],
            'not a number' => ['NaN'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidPrices')]
    public function test_invalid_prices_are_rejected_not_rounded(string $value): void
    {
        $this->actingAs($this->contributor())
            ->post(route('contributor.prices.store'), $this->validForm(['price_per_kg' => $value]))
            ->assertSessionHasErrors('price_per_kg');

        $this->assertSame(0, MarketPrice::count());
    }

    public function test_boundary_prices_are_accepted_exactly(): void
    {
        $contributor = $this->contributor();

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_per_kg' => '0.01']))->assertSessionHasNoErrors();
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_per_kg' => '100000', 'crop_code' => 'ginger']))->assertSessionHasNoErrors();

        $this->assertSame(['0.01', '100000.00'], MarketPrice::orderBy('id')->pluck('price_per_kg')->all());
    }

    public function test_price_dates_follow_the_bangladesh_calendar_and_a_one_year_window(): void
    {
        $contributor = $this->contributor();

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_date' => $this->daysFromToday(1)]))
            ->assertSessionHasErrors('price_date');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_date' => $this->daysFromToday(-366)]))
            ->assertSessionHasErrors('price_date');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_date' => '14/11/2026']))
            ->assertSessionHasErrors('price_date');
        $this->assertSame(0, MarketPrice::count());

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_date' => $this->daysFromToday(-365)]))
            ->assertSessionHasNoErrors();
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_date' => MarketPrice::today(), 'crop_code' => 'onion']))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, MarketPrice::count());
    }

    public function test_market_crop_and_type_must_come_from_their_lists(): void
    {
        $contributor = $this->contributor();

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['market_id' => 999999]))->assertSessionHasErrors('market_id');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['market_id' => 'Kawran Bazar']))->assertSessionHasErrors('market_id');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['crop_code' => 'gold']))->assertSessionHasErrors('crop_code');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_type' => 'black_market']))->assertSessionHasErrors('price_type');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['note' => str_repeat('n', 201)]))->assertSessionHasErrors('note');
        $this->actingAs($contributor)->post(route('contributor.prices.store'), [])
            ->assertSessionHasErrors(['market_id', 'crop_code', 'price_type', 'price_per_kg', 'price_date']);

        $this->assertSame(0, MarketPrice::count());
    }

    public function test_the_same_form_sent_twice_records_one_price(): void
    {
        $contributor = $this->contributor();
        $form = $this->validForm();

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $form)->assertSessionHasNoErrors();
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $form)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'This price was already recorded from your earlier submission. Nothing was added twice.');

        $this->assertSame(1, MarketPrice::count());
    }

    public function test_a_different_price_for_the_same_day_is_refused_and_history_is_unchanged(): void
    {
        $contributor = $this->contributor();
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_per_kg' => '35.50']));

        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_per_kg' => '40']))
            ->assertSessionHasErrors('price_date');

        $this->assertSame(1, MarketPrice::count());
        $this->assertSame('35.50', MarketPrice::sole()->price_per_kg);
    }

    public function test_new_days_types_and_other_contributors_add_new_history_rows(): void
    {
        $contributor = $this->contributor();
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_date' => $this->daysFromToday(-1), 'price_per_kg' => '34']));
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_per_kg' => '36']));
        $this->actingAs($contributor)->post(route('contributor.prices.store'), $this->validForm(['price_type' => 'wholesale', 'price_per_kg' => '30']));
        $this->actingAs($this->contributor())->post(route('contributor.prices.store'), $this->validForm(['price_per_kg' => '37']));

        // Yesterday's price is still there after today's: nothing was replaced.
        $this->assertSame(['34.00', '36.00', '30.00', '37.00'], MarketPrice::orderBy('id')->pluck('price_per_kg')->all());
    }

    /** The CHECK constraints stop impossible rows even if a bug or a manual SQL edit skipped the Form Requests. */
    public function test_the_database_refuses_impossible_values(): void
    {
        $price = MarketPrice::factory()->create();

        foreach ([
            ['price_per_kg' => '0'],
            ['price_per_kg' => '-1'],
            ['price_per_kg' => '100000.01'],
            ['crop_code' => 'gold'],
            ['price_type' => 'auction'],
            ['version' => 0],
        ] as $change) {
            try {
                DB::table('market_prices')->where('id', $price->id)->update($change);
                $this->fail('The database accepted '.json_encode($change));
            } catch (QueryException $e) {
                $this->assertStringContainsString('check constraint', strtolower($e->getMessage()));
            }
        }

        $this->assertSame('35.00', $price->fresh()->price_per_kg);
    }

    public function test_the_database_allows_one_entry_per_contributor_market_crop_type_and_day(): void
    {
        $price = MarketPrice::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('market_prices')->insert($price->only(['market_id', 'contributor_id', 'crop_code', 'price_type', 'price_date']) + ['price_per_kg' => '40', 'version' => 1]);
    }

    public function test_markets_and_contributors_with_prices_cannot_be_deleted_by_accident(): void
    {
        $price = MarketPrice::factory()->create();

        foreach ([['markets', $price->market_id], ['users', $price->contributor_id]] as [$table, $id]) {
            try {
                DB::table($table)->where('id', $id)->delete();
                $this->fail("The database deleted a {$table} row that has prices.");
            } catch (QueryException $e) {
                $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
            }
        }
    }
}
