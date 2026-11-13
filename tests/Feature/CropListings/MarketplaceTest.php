<?php

namespace Tests\Feature\CropListings;

use App\Models\CropListing;
use App\Services\CropListingPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F1: the public marketplace pages show published listings only, in a stable order, with bounded queries. */
class MarketplaceTest extends TestCase
{
    use CropListingHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(CropListingPhotoService::DISK);
    }

    public function test_guests_see_only_published_listings_newest_first(): void
    {
        $farmer = $this->farmer();
        CropListing::factory()->for($farmer, 'farmer')->create(['crop_name' => 'Older potato', 'published_at' => now()->subDays(2)]);
        CropListing::factory()->for($farmer, 'farmer')->create(['crop_name' => 'Newer rice', 'published_at' => now()->subDay()]);
        CropListing::factory()->for($farmer, 'farmer')->unpublished()->create(['crop_name' => 'Hidden onion']);
        CropListing::factory()->for($farmer, 'farmer')->create(['crop_name' => 'Removed chilli'])->delete();

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSeeInOrder(['Newer rice', 'Older potato'])
            ->assertDontSee('Hidden onion')
            ->assertDontSee('Removed chilli');
    }

    public function test_listings_published_at_the_same_moment_have_a_stable_order_across_pages(): void
    {
        $moment = Carbon::parse('2026-11-10 08:00:00');
        $listings = CropListing::factory()->count(13)->create(['published_at' => $moment]);

        $page1 = $this->get(route('marketplace.index'))->viewData('listings')->pluck('id')->all();
        $page2 = $this->get(route('marketplace.index', ['page' => 2]))->viewData('listings')->pluck('id')->all();

        $this->assertCount(12, $page1);
        $this->assertSame($listings->pluck('id')->sortDesc()->values()->all(), [...$page1, ...$page2], 'no duplicates or gaps');
    }

    public function test_the_public_page_shows_the_offer_but_no_private_account_details(): void
    {
        $farmer = $this->farmer();
        $farmer->forceFill(['email' => 'farmer@example.test'])->save();
        $listing = CropListing::factory()->for($farmer, 'farmer')->withPhotos(2)->create([
            'crop_name' => 'Aman rice', 'price_per_unit' => '42.50', 'quantity' => '1200.000', 'unit_code' => 'kg',
        ]);

        $this->get(route('marketplace.show', $listing))
            ->assertOk()
            ->assertSee('Aman rice')
            ->assertSee('42.50')
            ->assertSee('1200 kg available')
            ->assertSee('Described by the farmer')
            ->assertSee('Listed by a farmer')
            ->assertDontSee($farmer->phone)
            ->assertDontSee($farmer->name)
            ->assertDontSee('farmer@example.test')
            ->assertDontSee($listing->submission_token)
            ->assertDontSee($listing->photos()->first()->display_path)    // storage paths stay internal
            ->assertDontSee($listing->photos()->first()->thumbnail_path);
    }

    public function test_cart_and_offer_buttons_point_at_their_planned_routes(): void
    {
        $listing = CropListing::factory()->withPhotos()->create();

        $this->get(route('marketplace.show', $listing))
            ->assertOk()
            ->assertSee('action="'.route('cart.items.store').'"', false)
            ->assertSee('action="'.route('marketplace.offers.store', $listing).'"', false)
            ->assertSee('data-planned=', false)
            ->assertSee('Cart, ordering and price offers are coming soon.');

        // Until F5/F3 replace PlannedController, the routes say honestly that they are not built.
        $this->actingAs($this->buyer())->post(route('cart.items.store'), ['crop_listing_id' => $listing->id])->assertStatus(501);
    }

    public function test_the_owner_sees_manage_instead_of_buy_buttons(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);

        $this->actingAs($farmer)->get(route('marketplace.show', $listing))
            ->assertOk()
            ->assertSee('This is your listing')
            ->assertDontSee('Add to cart');
    }

    public function test_unknown_photo_variants_and_ids_are_not_found(): void
    {
        $listing = CropListing::factory()->withPhotos()->create();
        $photo = $listing->photos()->first();

        $this->get('/crop-listing-photos/'.$photo->id.'/original')->assertNotFound();
        $this->get('/crop-listing-photos/'.$photo->id.'/..%2F..%2F.env')->assertNotFound();
        $this->get('/crop-listing-photos/999999/thumb')->assertNotFound();
        $this->get($photo->url('thumb'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_the_list_page_uses_the_same_number_of_queries_for_1_or_12_cards(): void
    {
        CropListing::factory()->withPhotos()->create();
        $one = $this->countQueries(fn () => $this->get(route('marketplace.index'))->assertOk());

        CropListing::factory()->count(11)->withPhotos()->create();
        $twelve = $this->countQueries(fn () => $this->get(route('marketplace.index'))->assertOk());

        $this->assertSame($one, $twelve, 'no query per card (N+1)');
        $this->assertLessThanOrEqual(4, $twelve);
    }

    public function test_the_owner_list_uses_the_same_number_of_queries_for_1_or_12_cards(): void
    {
        $farmer = $this->farmer();
        // fresh(): a new User object each time, so its roles are not already cached from the previous request.
        CropListing::factory()->for($farmer, 'farmer')->withPhotos()->create();
        $one = $this->countQueries(fn () => $this->actingAs($farmer->fresh())->get(route('farmer.listings.index'))->assertOk());

        CropListing::factory()->count(11)->for($farmer, 'farmer')->withPhotos()->create();
        $twelve = $this->countQueries(fn () => $this->actingAs($farmer->fresh())->get(route('farmer.listings.index'))->assertOk());

        $this->assertSame($one, $twelve);
    }

    public function test_cards_load_thumbnails_only(): void
    {
        $listing = CropListing::factory()->withPhotos()->create();
        $photo = $listing->photos()->first();

        $this->get(route('marketplace.index'))
            ->assertSee($photo->url('thumb'), false)
            ->assertDontSee($photo->url('display'), false);
    }

    public function test_bangla_page_shows_bangla_digits_and_labels(): void
    {
        CropListing::factory()->create(['quantity' => '250.000', 'price_per_unit' => '35.00']);

        $this->withSession(['locale' => 'bn'])->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('৩৫')
            ->assertSee('২৫০');
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
