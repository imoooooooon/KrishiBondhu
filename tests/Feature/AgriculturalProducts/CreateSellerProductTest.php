<?php

namespace Tests\Feature\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** F25: adding a product: required details, exact numbers, bulk-offer rules, duplicate forms and the database guards. */
class CreateSellerProductTest extends TestCase
{
    use AgriculturalProductHelpers, RefreshDatabase;

    public function test_seller_publishes_a_complete_product(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->get(route('seller.products.create'))->assertOk()->assertSee('Add product');

        $response = $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm());

        $product = AgriculturalProduct::sole();
        $response->assertRedirect(route('seller.products.show', $product))->assertSessionHasNoErrors();
        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame('Urea fertilizer, 50 kg bag', $product->name);
        $this->assertSame('Jamuna', $product->brand);
        $this->assertSame('fertilizer', $product->category);
        $this->assertSame('bag', $product->unit_code);
        $this->assertSame('1350.50', $product->unit_price);
        $this->assertSame('40.000', $product->stock_quantity);
        $this->assertFalse($product->acceptsBulkOffers());
        $this->assertTrue($product->isPublished());
        $this->assertSame(1, $product->version);

        $this->actingAs($seller)->get(route('seller.products.show', $product))
            ->assertOk()
            ->assertSee('Your product is published.')
            ->assertSee('Not accepted. Every order uses the fixed price.');
    }

    public function test_owner_status_version_and_dates_cannot_be_sent_in(): void
    {
        $seller = $this->seller();
        $other = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm([
            'seller_id' => $other->id,
            'status' => 'unpublished',
            'version' => 99,
            'published_at' => '2001-01-01 00:00:00',
            'deleted_at' => '2001-01-01 00:00:00',
        ]))->assertSessionHasNoErrors();

        $product = AgriculturalProduct::withTrashed()->sole();
        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame('published', $product->status);
        $this->assertSame(1, $product->version);
        $this->assertNull($product->deleted_at);
        $this->assertTrue($product->published_at->isToday());
    }

    public function test_bangla_digits_are_stored_as_exact_numbers(): void
    {
        $this->actingAs($this->seller())->post(route('seller.products.store'), $this->bulkForm([
            'unit_code' => 'kg',
            'unit_price' => '৩৫.৭৫',
            'stock_quantity' => '১২৫০.৫',
            'bulk_min_quantity' => '১০০',
            'bulk_min_unit_price' => '৩২.৫০',
            'name' => 'ইউরিয়া সার',
        ]))->assertSessionHasNoErrors();

        $product = AgriculturalProduct::sole();
        $this->assertSame('35.75', $product->unit_price);
        $this->assertSame('1250.500', $product->stock_quantity);
        $this->assertSame('100.000', $product->bulk_min_quantity);
        $this->assertSame('32.50', $product->bulk_min_unit_price);
        $this->assertSame('ইউরিয়া সার', $product->name);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function invalidNumbers(): array
    {
        return [
            'zero price' => ['unit_price', '0'],
            'negative price' => ['unit_price', '-5'],
            'comma separator' => ['unit_price', '1,350'],
            'currency sign' => ['unit_price', '৳1350'],
            'scientific notation' => ['unit_price', '1e3'],
            'three price decimals' => ['unit_price', '13.555'],
            'price above maximum' => ['unit_price', '1000000.01'],
            'not a number' => ['unit_price', 'NaN'],
            'negative stock' => ['stock_quantity', '-1'],
            'four stock decimals' => ['stock_quantity', '1.2345'],
            'stock above maximum' => ['stock_quantity', '1000000.001'],
            'leading dot' => ['stock_quantity', '.5'],
            'empty stock' => ['stock_quantity', ''],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers_are_rejected_not_rounded(string $field, string $value): void
    {
        $this->actingAs($this->seller())
            ->post(route('seller.products.store'), $this->validForm([$field => $value]))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, AgriculturalProduct::withTrashed()->count());
    }

    public function test_boundary_numbers_are_accepted_exactly(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm([
            'unit_code' => 'kg', 'stock_quantity' => '1000000', 'unit_price' => '0.01',
        ]))->assertSessionHasNoErrors();
        // Zero stock is allowed: the product is listed as out of stock.
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm([
            'stock_quantity' => '0', 'unit_price' => '1000000',
        ]))->assertSessionHasNoErrors();

        [$first, $second] = AgriculturalProduct::orderBy('id')->get()->all();
        $this->assertSame('1000000.000', $first->stock_quantity);
        $this->assertSame('0.01', $first->unit_price);
        $this->assertSame('0.000', $second->stock_quantity);
        $this->assertTrue($second->isOutOfStock());
        $this->assertSame('1000000.00', $second->unit_price);
    }

    public function test_counted_units_need_whole_numbers(): void
    {
        $seller = $this->seller();

        foreach (['bag', 'packet', 'bottle', 'piece'] as $unit) {
            $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['unit_code' => $unit, 'stock_quantity' => '2.5']))
                ->assertSessionHasErrors('stock_quantity');
        }
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['unit_code' => 'litre', 'stock_quantity' => '2.5']))
            ->assertSessionHasNoErrors();
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['unit_code' => 'piece', 'stock_quantity' => '40.000']))
            ->assertSessionHasNoErrors();

        $this->assertSame(['2.500', '40.000'], AgriculturalProduct::orderBy('id')->pluck('stock_quantity')->all());
    }

    /** @return array<string, array{0: string}> */
    public static function requiredFields(): array
    {
        return ['name' => ['name'], 'category' => ['category'], 'description' => ['description'],
            'unit' => ['unit_code'], 'price' => ['unit_price'], 'stock' => ['stock_quantity']];
    }

    #[DataProvider('requiredFields')]
    public function test_each_required_detail_is_checked_before_publication(string $field): void
    {
        $this->actingAs($this->seller())
            ->post(route('seller.products.store'), $this->validForm([$field => '']))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, AgriculturalProduct::count());
    }

    public function test_text_limits_and_codes_are_checked(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm([
            'name' => str_repeat('a', 121),
            'brand' => str_repeat('b', 81),
            'description' => 'too short',
            'category' => 'weapons',
            'unit_code' => 'tonne',
        ]))->assertSessionHasErrors(['name', 'brand', 'description', 'category', 'unit_code']);

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm([
            'name' => str_repeat('a', 120), 'brand' => '', 'description' => str_repeat('d', 1000),
        ]))->assertSessionHasNoErrors();

        $this->assertNull(AgriculturalProduct::sole()->brand); // an empty brand is stored as "no brand"
    }

    public function test_area_and_delivery_options_are_saved(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm([
            'public_area' => 'Mohasthan, Bogura',
            'delivery_options' => ['third_party', 'pickup'],
            'delivery_notes' => 'Courier to any district.',
        ]))->assertSessionHasNoErrors();

        $product = AgriculturalProduct::sole();
        $this->assertSame('Mohasthan, Bogura', $product->public_area);
        $this->assertSame('Courier to any district.', $product->delivery_notes);
        $this->assertEqualsCanonicalizing(['third_party', 'pickup'], $product->deliveryMethods());

        $this->actingAs($seller)->get(route('seller.products.show', $product))
            ->assertSee('Mohasthan, Bogura')
            ->assertSee('Third-party delivery (courier)')
            ->assertSee('Buyer picks up from the seller')
            ->assertSee('Courier to any district.');
    }

    public function test_area_and_delivery_options_are_checked(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['public_area' => '']))
            ->assertSessionHasErrors('public_area');
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['public_area' => str_repeat('a', 121)]))
            ->assertSessionHasErrors('public_area');
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['delivery_options' => []]))
            ->assertSessionHasErrors('delivery_options');
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['delivery_options' => ['teleport']]))
            ->assertSessionHasErrors('delivery_options.0');
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['delivery_options' => ['pickup', 'pickup']]))
            ->assertSessionHasErrors('delivery_options.0');
        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['delivery_notes' => str_repeat('n', 301)]))
            ->assertSessionHasErrors('delivery_notes');

        $this->assertSame(0, AgriculturalProduct::count());
    }

    public function test_bulk_offers_store_the_threshold_and_the_private_minimum(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm())->assertSessionHasNoErrors();

        $product = AgriculturalProduct::sole();
        $this->assertTrue($product->acceptsBulkOffers());
        $this->assertSame('10.000', $product->bulk_min_quantity);
        $this->assertSame('1250.00', $product->bulk_min_unit_price);

        // The owner's page shows the private minimum, marked as private.
        $this->actingAs($seller)->get(route('seller.products.show', $product))
            ->assertOk()
            ->assertSee('Accepted from 10 bag')
            ->assertSee('1,250')
            ->assertSee('Private. Buyers never see this price.');
    }

    public function test_bulk_fields_are_ignored_when_bulk_offers_are_not_ticked(): void
    {
        $this->actingAs($this->seller())->post(route('seller.products.store'), $this->validForm([
            'bulk_min_quantity' => '10',
            'bulk_min_unit_price' => '1',
        ]))->assertSessionHasNoErrors();

        $product = AgriculturalProduct::sole();
        $this->assertNull($product->bulk_min_quantity);
        $this->assertNull($product->bulk_min_unit_price);
    }

    public function test_bulk_offers_need_both_the_threshold_and_the_minimum_price(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['bulk_min_quantity' => '']))
            ->assertSessionHasErrors('bulk_min_quantity');
        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['bulk_min_unit_price' => '']))
            ->assertSessionHasErrors('bulk_min_unit_price');

        $this->assertSame(0, AgriculturalProduct::count());
    }

    public function test_the_bulk_minimum_price_may_equal_but_never_exceed_the_fixed_price(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['unit_price' => '1350.50', 'bulk_min_unit_price' => '1350.51']))
            ->assertSessionHasErrors('bulk_min_unit_price');
        $this->assertSame(0, AgriculturalProduct::count());

        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['unit_price' => '1350.50', 'bulk_min_unit_price' => '1350.50']))
            ->assertSessionHasNoErrors();
        $this->assertSame('1350.50', AgriculturalProduct::sole()->bulk_min_unit_price);
    }

    public function test_bulk_minimum_price_limits(): void
    {
        $seller = $this->seller();

        foreach (['0', '-1', '12.345', '1,250'] as $price) {
            $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['bulk_min_unit_price' => $price]))
                ->assertSessionHasErrors('bulk_min_unit_price');
        }

        $this->assertSame(0, AgriculturalProduct::count());
    }

    public function test_bulk_threshold_limits(): void
    {
        $seller = $this->seller();

        // A bulk order is for at least 2 units; 1 unit is always a fixed-price purchase.
        foreach (['0', '1', '1.999', '1000000.001'] as $quantity) {
            $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['unit_code' => 'kg', 'bulk_min_quantity' => $quantity]))
                ->assertSessionHasErrors('bulk_min_quantity');
        }
        // Counted units: the threshold is a whole number too.
        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['bulk_min_quantity' => '10.5']))
            ->assertSessionHasErrors('bulk_min_quantity');
        $this->assertSame(0, AgriculturalProduct::count());

        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['bulk_min_quantity' => '2', 'bulk_min_unit_price' => '0.01']))
            ->assertSessionHasNoErrors();
        $this->actingAs($seller)->post(route('seller.products.store'), $this->bulkForm(['unit_code' => 'kg', 'stock_quantity' => '5', 'bulk_min_quantity' => '1000000']))
            ->assertSessionHasNoErrors();
        $this->assertSame(['2.000', '1000000.000'], AgriculturalProduct::orderBy('id')->pluck('bulk_min_quantity')->all());
    }

    public function test_the_same_form_sent_twice_adds_one_product(): void
    {
        $seller = $this->seller();
        $form = $this->validForm();

        $this->actingAs($seller)->post(route('seller.products.store'), $form)->assertSessionHasNoErrors();
        $this->actingAs($seller)->post(route('seller.products.store'), $form)
            ->assertRedirect(route('seller.products.show', AgriculturalProduct::sole()))
            ->assertSessionHas('status', 'This product was already added from your earlier submission. Nothing was added twice.');

        $this->assertSame(1, AgriculturalProduct::count());
    }

    public function test_resending_the_form_of_a_removed_product_does_not_bring_it_back(): void
    {
        $seller = $this->seller();
        $form = $this->validForm();
        $this->actingAs($seller)->post(route('seller.products.store'), $form);
        $product = AgriculturalProduct::sole();
        $this->actingAs($seller)->delete(route('seller.products.destroy', $product), ['confirm_remove' => '1', 'version' => 1]);

        $this->actingAs($seller)->post(route('seller.products.store'), $form)
            ->assertRedirect(route('seller.products.index'))
            ->assertSessionHas('product_warning');

        $this->assertSame(0, AgriculturalProduct::count());
        $this->assertSame(1, AgriculturalProduct::withTrashed()->count());
    }

    public function test_the_same_token_from_two_sellers_makes_two_products(): void
    {
        $form = $this->validForm();

        $this->actingAs($this->seller())->post(route('seller.products.store'), $form)->assertSessionHasNoErrors();
        $this->actingAs($this->seller())->post(route('seller.products.store'), $form)->assertSessionHasNoErrors();

        $this->assertSame(2, AgriculturalProduct::count());
    }

    public function test_the_form_keeps_text_and_shows_linked_errors_after_a_failure(): void
    {
        $seller = $this->seller();
        $form = $this->bulkForm(['unit_price' => '-1', 'name' => 'Kept name']);

        $this->actingAs($seller)->from(route('seller.products.create'))->post(route('seller.products.store'), $form)
            ->assertRedirect(route('seller.products.create'));

        $this->actingAs($seller)->get(route('seller.products.create'))
            ->assertOk()
            ->assertSee('Please fix 1 problem.')
            ->assertSee('href="#field-unit_price"', false)
            ->assertSee('value="Kept name"', false)
            ->assertSee('value="'.$form['submission_token'].'"', false);
    }

    /** The CHECK constraints stop impossible rows even if a bug or a manual SQL edit skipped the Form Requests. */
    public function test_the_database_refuses_impossible_values(): void
    {
        $product = AgriculturalProduct::factory()->withBulk()->create();

        foreach ([
            ['stock_quantity' => '-1'],
            ['unit_price' => '0'],
            ['stock_quantity' => '2.5'],                 // bag is a counted unit
            ['bulk_min_unit_price' => '9999'],           // above the fixed price of 1350
            ['bulk_min_unit_price' => null],             // one bulk field without the other
            ['bulk_min_quantity' => '1'],                // below the bulk threshold minimum
            ['category' => 'weapons'],
            ['unit_code' => 'tonne'],
            ['status' => 'draft'],
        ] as $change) {
            try {
                DB::table('agricultural_products')->where('id', $product->id)->update($change);
                $this->fail('The database accepted '.json_encode($change));
            } catch (QueryException $e) {
                $this->assertStringContainsString('check constraint', strtolower($e->getMessage()));
            }
        }

        $this->assertSame('40.000', $product->fresh()->stock_quantity);
    }

    public function test_the_database_refuses_an_unknown_delivery_method(): void
    {
        $product = AgriculturalProduct::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('agricultural_product_delivery_options')->insert(['agricultural_product_id' => $product->id, 'method' => 'teleport']);
    }

    public function test_a_seller_with_products_cannot_be_deleted_by_accident(): void
    {
        $seller = $this->seller();
        $this->productFor($seller);

        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $seller->id)->delete();
    }
}
