<?php

namespace Tests\Feature\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** F25: the public shop shows published products only, filters exactly, separates fixed price from bulk offers, never leaks the minimum. */
class ShopBrowseTest extends TestCase
{
    use AgriculturalProductHelpers, RefreshDatabase;

    public function test_guests_see_published_products_only(): void
    {
        AgriculturalProduct::factory()->create(['name' => 'Published urea']);
        AgriculturalProduct::factory()->unpublished()->create(['name' => 'Hidden potash']);
        AgriculturalProduct::factory()->create(['name' => 'Removed seed'])->delete();

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('Published urea')
            ->assertDontSee('Hidden potash')
            ->assertDontSee('Removed seed')
            ->assertSee('1 product found');
    }

    public function test_unpublished_removed_and_unknown_products_are_all_not_found(): void
    {
        $seller = $this->seller();
        $hidden = AgriculturalProduct::factory()->for($seller, 'seller')->unpublished()->create();
        $removed = AgriculturalProduct::factory()->for($seller, 'seller')->create();
        $removed->delete();

        $this->get(route('shop.show', $hidden))->assertNotFound();
        $this->get(route('shop.show', $removed->id))->assertNotFound();
        $this->get(route('shop.show', 999999))->assertNotFound();
        // Even the owner previews a hidden product from the seller workspace, not the public page.
        $this->actingAs($seller)->get(route('shop.show', $hidden))->assertNotFound();
    }

    public function test_any_signed_in_user_can_browse(): void
    {
        $product = $this->productFor($this->seller());

        foreach ([Role::BUYER, Role::FARMER, Role::WORKER] as $role) {
            $this->actingAs($this->userWith($role))->get(route('shop.show', $product))->assertOk()->assertSee($product->name);
        }
    }

    public function test_the_list_is_newest_first_in_a_stable_order(): void
    {
        $old = AgriculturalProduct::factory()->create(['name' => 'Older product', 'published_at' => now()->subDays(2)]);
        $sameA = AgriculturalProduct::factory()->create(['name' => 'Same time A', 'published_at' => now()->subHour()]);
        $sameB = AgriculturalProduct::factory()->create(['name' => 'Same time B', 'published_at' => $sameA->published_at]);
        $new = AgriculturalProduct::factory()->create(['name' => 'Newest product', 'published_at' => now()]);

        $this->get(route('shop.index'))->assertSeeInOrder(['Newest product', 'Same time B', 'Same time A', 'Older product']);
    }

    public function test_product_page_separates_fixed_price_and_bulk_offers_and_hides_the_minimum(): void
    {
        $product = AgriculturalProduct::factory()->withBulk('10.000', '1234.56')->create(['unit_price' => '1350.00', 'stock_quantity' => '40.000']);

        $response = $this->get(route('shop.show', $product))->assertOk();

        $response->assertSee('Buy at the fixed price')
            ->assertSee('1,350')
            ->assertSee('40 bag in stock')
            ->assertSee('Bulk order')
            ->assertSee('For 10 bag or more, you can offer your own price per bag.')
            ->assertSee('Make a bulk offer')
            ->assertDontSee('1234.56')
            ->assertDontSee('1,234')
            ->assertDontSee('lowest bulk price');
    }

    public function test_the_shop_list_never_shows_the_minimum_either(): void
    {
        AgriculturalProduct::factory()->withBulk('10.000', '1234.56')->create();

        $this->get(route('shop.index'))->assertOk()->assertSee('Bulk offers')->assertDontSee('1234.56')->assertDontSee('1,234');
    }

    public function test_the_listing_time_is_on_the_product_page_not_on_the_cards(): void
    {
        // 22:15 UTC on 13 Nov is 04:15 on 14 Nov in Bangladesh (UTC+6).
        $product = AgriculturalProduct::factory()->create(['published_at' => '2026-11-13 22:15:00']);

        $this->get(route('shop.show', $product))->assertSee('Listed 14 Nov 2026, 4:15 am');
        $this->get(route('shop.index'))->assertOk()->assertDontSee('Listed 14 Nov 2026');
    }

    public function test_cards_show_the_area_before_opening_the_product(): void
    {
        AgriculturalProduct::factory()->create(['name' => 'Urea in Rajshahi', 'public_area' => 'Paba, Rajshahi']);

        $this->get(route('shop.index'))->assertOk()->assertSeeInOrder(['Urea in Rajshahi', 'Paba, Rajshahi']);
    }

    public function test_product_page_shows_area_and_delivery_in_the_details_and_the_buy_section(): void
    {
        $product = AgriculturalProduct::factory()->create(['public_area' => 'Paba, Rajshahi', 'delivery_notes' => 'Courier to any district.']);
        $product->deliveryOptions()->create(['method' => 'third_party']);

        $html = $this->get(route('shop.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('Sold from Paba, Rajshahi', $html);
        $this->assertStringContainsString('Delivery cost is not included in the price.', $html);
        // Listed twice: in "Product details" and again where the buyer decides to buy.
        $this->assertSame(2, substr_count($html, 'Third-party delivery (courier)'));
        $this->assertSame(2, substr_count($html, 'Buyer picks up from the seller'));
        $this->assertSame(2, substr_count($html, 'Courier to any district.'));
    }

    public function test_keyword_search_also_finds_the_area(): void
    {
        AgriculturalProduct::factory()->create(['name' => 'Rajshahi urea', 'public_area' => 'Paba, Rajshahi']);
        AgriculturalProduct::factory()->create(['name' => 'Bogura urea', 'public_area' => 'Shibganj, Bogura']);

        $this->get(route('shop.index', ['q' => 'Rajshahi']))->assertSee('Rajshahi urea')->assertDontSee('Bogura urea');
    }

    public function test_a_fixed_price_only_product_says_so(): void
    {
        $product = $this->productFor($this->seller());

        $this->get(route('shop.show', $product))
            ->assertSee('This seller sells at the fixed price only.')
            ->assertDontSee('Make a bulk offer');
    }

    public function test_bulk_offers_say_when_stock_is_below_the_threshold(): void
    {
        $product = AgriculturalProduct::factory()->withBulk('50.000', '1250.00')->create(['stock_quantity' => '40.000']);

        $this->get(route('shop.show', $product))->assertSee('There is not enough stock for a bulk order right now.');
    }

    public function test_out_of_stock_products_are_marked_and_cannot_be_bought(): void
    {
        $product = AgriculturalProduct::factory()->outOfStock()->create(['name' => 'Empty urea']);

        $this->get(route('shop.index'))->assertSee('Empty urea')->assertSee('Out of stock');
        $this->get(route('shop.show', $product))
            ->assertSee('This product is out of stock and cannot be bought until the seller restocks.');
    }

    public function test_buying_buttons_are_disabled_until_cart_and_offers_exist(): void
    {
        $product = AgriculturalProduct::factory()->withBulk()->create();

        $html = $this->get(route('shop.show', $product))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>.*?Add to cart/s', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>.*?Make a bulk offer/s', $html);
        $this->assertStringContainsString('Ordering online is not available yet', $html);
    }

    public function test_keyword_search_covers_name_brand_and_description_and_escapes_wildcards(): void
    {
        AgriculturalProduct::factory()->create(['name' => 'Urea 50 kg', 'brand' => null, 'description' => 'Nitrogen fertilizer for rice.']);
        AgriculturalProduct::factory()->create(['name' => 'Granules', 'brand' => 'Jamuna Urea', 'description' => 'Sealed bag of granules.']);
        AgriculturalProduct::factory()->create(['name' => 'Insect spray', 'description' => 'For stem borer, 100% coverage.']);

        $this->get(route('shop.index', ['q' => 'urea']))->assertSee('Urea 50 kg')->assertSee('Granules')->assertDontSee('Insect spray');
        $this->get(route('shop.index', ['q' => 'stem borer']))->assertSee('Insect spray')->assertDontSee('Urea 50 kg');
        // "%" is searched as a normal character, not as "match everything".
        $this->get(route('shop.index', ['q' => '%']))->assertSee('Insect spray')->assertDontSee('Urea 50 kg')->assertDontSee('Granules');
    }

    public function test_category_price_and_stock_filters_use_exact_values(): void
    {
        AgriculturalProduct::factory()->create(['name' => 'Cheap seed', 'category' => 'seed', 'unit_price' => '99.99']);
        AgriculturalProduct::factory()->create(['name' => 'Exact seed', 'category' => 'seed', 'unit_price' => '100.00']);
        AgriculturalProduct::factory()->create(['name' => 'Dear seed', 'category' => 'seed', 'unit_price' => '100.01', 'stock_quantity' => '0']);
        AgriculturalProduct::factory()->create(['name' => 'A sprayer', 'category' => 'tool', 'unit_price' => '100.00']);

        $this->get(route('shop.index', ['category' => 'seed', 'min_price' => '100', 'max_price' => '100.00']))
            ->assertSee('Exact seed')->assertDontSee('Cheap seed')->assertDontSee('Dear seed')->assertDontSee('A sprayer');
        $this->get(route('shop.index', ['category' => 'seed', 'in_stock' => '1']))
            ->assertSee('Cheap seed')->assertSee('Exact seed')->assertDontSee('Dear seed');
        // Bangla digits work in the price filters.
        $this->get(route('shop.index', ['max_price' => '৯৯.৯৯']))->assertSee('Cheap seed')->assertDontSee('Exact seed');
    }

    public function test_invalid_filters_return_to_the_shop_with_an_error(): void
    {
        foreach ([['category' => 'weapons'], ['min_price' => '1,000'], ['max_price' => '-5'], ['min_price' => '500', 'max_price' => '100']] as $filters) {
            $this->get(route('shop.index', $filters))->assertRedirect(route('shop.index'))->assertSessionHasErrors();
        }
    }

    public function test_filters_stay_in_the_page_links(): void
    {
        AgriculturalProduct::factory()->count(AgriculturalProduct::PER_PAGE + 1)->create(['category' => 'pesticide']);

        $this->get(route('shop.index', ['category' => 'pesticide']))
            ->assertOk()
            ->assertSee('category=pesticide&amp;page=2', false);
    }

    public function test_seller_text_is_shown_as_text_never_as_html(): void
    {
        $product = AgriculturalProduct::factory()->create([
            'name' => '<script>alert(1)</script>Urea',
            'brand' => '"><svg onload=alert(3)>',
            'description' => '<img src=x onerror=alert(2)>',
            'public_area' => '<iframe src=x>',
            'delivery_notes' => '<b onmouseover=alert(4)>',
        ]);

        foreach ([$this->get(route('shop.index')), $this->get(route('shop.show', $product)), $this->get(route('shop.index', ['q' => '<script>']))] as $response) {
            $response->assertOk()
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('<img src=x onerror', false)
                ->assertDontSee('<svg onload', false)
                ->assertDontSee('<iframe src=x>', false)
                ->assertDontSee('<b onmouseover', false);
        }
    }

    public function test_the_owner_sees_a_link_back_to_their_workspace(): void
    {
        $seller = $this->seller();
        $product = $this->productFor($seller);

        $this->actingAs($seller)->get(route('shop.show', $product))->assertSee('This is your product')->assertSee(route('seller.products.show', $product));
        $this->actingAs($this->userWith(Role::BUYER))->get(route('shop.show', $product))->assertDontSee('This is your product');
        $this->actingAs($seller)->get(route('seller.products.show', $product))->assertSee(route('shop.show', $product));
    }

    public function test_the_shop_list_uses_the_same_number_of_queries_for_1_or_12_products(): void
    {
        AgriculturalProduct::factory()->create();
        $one = $this->countQueries(fn () => $this->get(route('shop.index'))->assertOk());

        AgriculturalProduct::factory()->count(11)->withBulk()->create();
        $twelve = $this->countQueries(fn () => $this->get(route('shop.index'))->assertOk());

        $this->assertSame($one, $twelve, 'no query per card (N+1)');
        $this->assertLessThanOrEqual(4, $twelve);
    }

    public function test_bangla_shop_shows_bangla_digits(): void
    {
        $product = AgriculturalProduct::factory()->create(['unit_price' => '1350.00', 'stock_quantity' => '40.000']);

        $this->withSession(['locale' => 'bn'])->get(route('shop.show', $product))
            ->assertOk()
            ->assertSee('১,৩৫০')
            ->assertSee('৪০');
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
