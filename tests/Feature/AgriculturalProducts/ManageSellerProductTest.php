<?php

namespace Tests\Feature\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** F25: editing price, stock and bulk settings; hiding and publishing again; removal; stale tabs; the list page. */
class ManageSellerProductTest extends TestCase
{
    use AgriculturalProductHelpers, RefreshDatabase;

    public function test_editing_keeps_the_status_and_raises_the_version(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);

        $this->actingAs($seller)->get(route('seller.products.edit', $product))->assertOk();
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, [
            'unit_price' => '1400',
            'stock_quantity' => '0',
            'brand' => '',
        ]))->assertRedirect(route('seller.products.show', $product))->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertSame('1400.00', $fresh->unit_price);
        $this->assertSame('0.000', $fresh->stock_quantity);
        $this->assertTrue($fresh->isOutOfStock());
        $this->assertNull($fresh->brand);
        $this->assertTrue($fresh->isPublished());
        $this->assertSame(2, $fresh->version);
    }

    public function test_editing_a_hidden_product_keeps_it_hidden(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->unpublished()->create();

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, ['name' => 'TSP fertilizer']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($product->fresh()->isPublished());
    }

    public function test_status_version_and_owner_cannot_be_changed_through_the_edit_form(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, [
            'seller_id' => $this->seller()->id,
            'status' => 'unpublished',
            'published_at' => '2001-01-01 00:00:00',
        ]))->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertSame($seller->id, $fresh->seller_id);
        $this->assertTrue($fresh->isPublished());
        $this->assertSame(2, $fresh->version);
        $this->assertNotSame('2001', $fresh->published_at->format('Y'));
    }

    public function test_editing_changes_the_area_and_syncs_delivery_options(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller); // factory default: pickup only

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, [
            'public_area' => 'Savar, Dhaka',
            'delivery_options' => ['seller_arranged', 'third_party'],
            'delivery_notes' => 'Delivery inside Savar on Fridays.',
        ]))->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertSame('Savar, Dhaka', $fresh->public_area);
        $this->assertEqualsCanonicalizing(['seller_arranged', 'third_party'], $fresh->deliveryMethods());
        $this->assertSame('Delivery inside Savar on Fridays.', $fresh->delivery_notes);
    }

    public function test_bulk_offers_can_be_turned_on_changed_and_turned_off(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, [
            'accepts_bulk_offers' => '1', 'bulk_min_quantity' => '20', 'bulk_min_unit_price' => '1300',
        ]))->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertSame('20.000', $product->bulk_min_quantity);
        $this->assertSame('1300.00', $product->bulk_min_unit_price);

        // Unticking "Accept bulk offers" clears both fields, even if their old values are still sent.
        $form = $this->editForm($product);
        unset($form['accepts_bulk_offers']);
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $form)->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertFalse($fresh->acceptsBulkOffers());
        $this->assertNull($fresh->bulk_min_quantity);
        $this->assertNull($fresh->bulk_min_unit_price);
    }

    public function test_lowering_the_fixed_price_below_the_bulk_minimum_is_refused(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->withBulk('10.000', '1250.00')->create();

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, ['unit_price' => '1249.99']))
            ->assertSessionHasErrors('bulk_min_unit_price');
        $this->assertSame('1350.00', $product->fresh()->unit_price);

        // Lowering both together works, and the price may equal the bulk minimum.
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, ['unit_price' => '1200', 'bulk_min_unit_price' => '1200']))
            ->assertSessionHasNoErrors();
        $this->assertSame('1200.00', $product->fresh()->unit_price);
    }

    public function test_a_save_from_an_old_tab_is_refused_with_a_conflict_page(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);
        $tabA = $this->editForm($product, ['stock_quantity' => '35']);
        $tabB = $this->editForm($product, ['stock_quantity' => '90', 'name' => 'Old tab text']);

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $tabA)->assertSessionHasNoErrors();
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $tabB)
            ->assertStatus(409)
            ->assertSee('Your changes were not saved.')
            ->assertSee('35 bag in stock')
            ->assertSee('Old tab text');

        $fresh = $product->fresh();
        $this->assertSame('35.000', $fresh->stock_quantity, 'the stale tab did not overwrite the newer stock');
        $this->assertSame(2, $fresh->version);
    }

    public function test_hiding_and_publishing_again(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);
        $firstPublished = $product->published_at->toDateTimeString();

        $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'unpublish', 'version' => 1])
            ->assertRedirect(route('seller.products.show', $product));
        $this->assertFalse($product->fresh()->isPublished());
        $this->actingAs($seller)->get(route('seller.products.show', $product))->assertSee('Hidden from buyers')->assertSee('Publish again');

        $this->travel(2)->days();
        $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'publish', 'version' => 2])
            ->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertTrue($fresh->isPublished());
        $this->assertSame(3, $fresh->version);
        $this->assertSame($firstPublished, $fresh->published_at->toDateTimeString(), 'republishing keeps the first publication time');
    }

    public function test_a_product_with_no_stock_can_be_published(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->unpublished()->outOfStock()->create();

        $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'publish', 'version' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue($product->fresh()->isPublished());
        $this->actingAs($seller)->get(route('seller.products.show', $product))->assertSee('Out of stock');
    }

    public function test_a_visibility_change_from_an_old_page_is_refused(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, ['stock_quantity' => '12']));

        $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'unpublish', 'version' => 1])
            ->assertStatus(409)
            ->assertSee('You pressed a button (publish, unpublish or remove) on an old copy of the page.');

        $this->assertTrue($product->fresh()->isPublished());
    }

    public function test_unknown_operations_and_missing_versions_are_refused(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);

        $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'delete', 'version' => 1])
            ->assertSessionHasErrors('operation');
        $this->actingAs($seller)->patch(route('seller.products.update', $product), ['operation' => 'unpublish'])
            ->assertSessionHasErrors('version');

        $this->assertSame(1, $product->fresh()->version);
    }

    public function test_removal_needs_the_confirmation_and_the_current_version_and_keeps_the_row(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);

        $this->actingAs($seller)->get(route('seller.products.show', [$product, 'confirm' => 'remove']))
            ->assertOk()->assertSee('Remove this product?');
        $this->assertNotNull($product->fresh(), 'opening the confirmation link changes nothing');

        $this->actingAs($seller)->delete(route('seller.products.destroy', $product), ['version' => 1])
            ->assertSessionHasErrors('confirm_remove');
        $this->actingAs($seller)->delete(route('seller.products.destroy', $product), ['confirm_remove' => '1', 'version' => 7])
            ->assertStatus(409);
        $this->assertNotNull($product->fresh());

        $this->actingAs($seller)->delete(route('seller.products.destroy', $product), ['confirm_remove' => '1', 'version' => 1])
            ->assertRedirect(route('seller.products.index'));

        // Soft delete: the row stays for future order history, but the product is gone from the app.
        $removed = AgriculturalProduct::withTrashed()->find($product->id);
        $this->assertNotNull($removed->deleted_at);
        $this->assertSame(2, $removed->version);
        $this->actingAs($seller)->get(route('seller.products.show', $product))->assertNotFound();
        $this->actingAs($seller)->get(route('seller.products.edit', $product))->assertNotFound();
        $this->actingAs($seller)->get(route('seller.products.index'))->assertDontSee($product->name);
    }

    public function test_status_filter_on_the_product_list(): void
    {
        $seller = $this->seller();
        AgriculturalProduct::factory()->for($seller, 'seller')->create(['name' => 'Visible urea']);
        AgriculturalProduct::factory()->for($seller, 'seller')->unpublished()->create(['name' => 'Hidden potash']);

        $this->actingAs($seller)->get(route('seller.products.index', ['status' => 'unpublished']))
            ->assertOk()->assertSee('Hidden potash')->assertDontSee('Visible urea')->assertSee('(1)');
        $this->actingAs($seller)->get(route('seller.products.index', ['status' => 'published']))
            ->assertOk()->assertSee('Visible urea')->assertDontSee('Hidden potash');
        $this->actingAs($seller)->get(route('seller.products.index', ['status' => '<b>x</b>']))
            ->assertOk()->assertSee('That filter does not exist')->assertSee('Visible urea')->assertSee('Hidden potash');
    }

    public function test_the_empty_list_invites_the_seller_to_add_a_product(): void
    {
        $this->actingAs($this->seller())->get(route('seller.products.index'))
            ->assertOk()->assertSee('You have no products');
    }

    public function test_the_edit_form_shows_exact_saved_values(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->withBulk('12.000', '33.25')->create([
            'unit_code' => 'kg', 'unit_price' => '35.50', 'stock_quantity' => '1250.500',
        ]);

        $this->actingAs($seller)->get(route('seller.products.edit', $product))
            ->assertOk()
            ->assertSee('value="35.5"', false)
            ->assertSee('value="1250.5"', false)
            ->assertSee('value="12"', false)
            ->assertSee('value="33.25"', false)
            ->assertSee('name="version" value="1"', false);
    }

    public function test_the_product_list_uses_the_same_number_of_queries_for_1_or_12_products(): void
    {
        $seller = $this->seller();
        // fresh(): a new User object each time, so its roles are not already cached from the previous request.
        AgriculturalProduct::factory()->for($seller, 'seller')->create();
        $one = $this->countQueries(fn () => $this->actingAs($seller->fresh())->get(route('seller.products.index'))->assertOk());

        AgriculturalProduct::factory()->count(11)->for($seller, 'seller')->withBulk()->create();
        $twelve = $this->countQueries(fn () => $this->actingAs($seller->fresh())->get(route('seller.products.index'))->assertOk());

        $this->assertSame($one, $twelve, 'no query per card (N+1)');
    }

    public function test_bangla_pages_show_bangla_digits(): void
    {
        // A signed-in user's saved language wins over the session (SetLocale), so save Bangla on the account.
        $seller = $this->seller();
        $seller->forceFill(['locale' => 'bn'])->save();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->withBulk('10.000', '1250.00')->create(['stock_quantity' => '40.000']);

        $this->actingAs($seller)->get(route('seller.products.show', $product))
            ->assertOk()
            ->assertSee('৪০')
            ->assertSee('১০')
            ->assertSee('১,৩৫০');
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
