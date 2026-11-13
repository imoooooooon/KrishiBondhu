<?php

namespace Tests\Feature\CropListings;

use App\Models\CropListing;
use App\Services\CropListingPhotoService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * F1: who may do what (spec §5, §16). Every request is sent directly, as an attacker would,
 * instead of relying on buttons being hidden.
 */
class CropListingAccessTest extends TestCase
{
    use CropListingHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(CropListingPhotoService::DISK);
    }

    public function test_guests_are_sent_to_log_in_from_every_owner_page(): void
    {
        $listing = $this->listingFor($this->farmer());

        foreach ([
            $this->get(route('farmer.listings.index')),
            $this->get(route('farmer.listings.create')),
            $this->get(route('farmer.listings.show', $listing)),
            $this->get(route('farmer.listings.edit', $listing)),
            $this->post(route('farmer.listings.store'), $this->validForm()),
            $this->patch(route('farmer.listings.update', $listing), ['operation' => 'unpublish', 'version' => 1]),
            $this->delete(route('farmer.listings.destroy', $listing), ['confirm_remove' => '1', 'version' => 1]),
        ] as $response) {
            $response->assertRedirect(route('login'));
        }

        $this->assertTrue($listing->fresh()->isPublished());
    }

    public function test_signed_in_non_farmers_have_no_listing_workspace(): void
    {
        $buyer = $this->buyer();
        $listing = $this->listingFor($this->farmer());

        $this->actingAs($buyer)->get(route('farmer.listings.index'))->assertForbidden();
        $this->actingAs($buyer)->get(route('farmer.listings.create'))->assertForbidden();
        $this->actingAs($buyer)->post(route('farmer.listings.store'), $this->validForm())->assertForbidden();
        $this->actingAs($buyer)->get(route('farmer.listings.edit', $listing))->assertForbidden();
        $this->actingAs($buyer)->patch(route('farmer.listings.update', $listing), ['operation' => 'unpublish', 'version' => 1])->assertForbidden();

        $this->assertSame(1, CropListing::count());
        $this->assertTrue($listing->fresh()->isPublished());
        // ...but they can still read the public marketplace.
        $this->actingAs($buyer)->get(route('marketplace.show', $listing))->assertOk();
    }

    public function test_another_farmer_gets_not_found_for_every_owner_action(): void
    {
        $owner = $this->farmer();
        $intruder = $this->farmer();
        $listing = $this->listingFor($owner);
        $version = $listing->version;

        $this->actingAs($intruder)->get(route('farmer.listings.show', $listing))->assertNotFound();
        $this->actingAs($intruder)->get(route('farmer.listings.edit', $listing))->assertNotFound();
        $this->actingAs($intruder)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, ['crop_name' => 'Stolen']))->assertNotFound();
        $this->actingAs($intruder)->patch(route('farmer.listings.update', $listing), ['operation' => 'unpublish', 'version' => $version])->assertNotFound();
        $this->actingAs($intruder)->delete(route('farmer.listings.destroy', $listing), ['confirm_remove' => '1', 'version' => $version])->assertNotFound();

        $fresh = $listing->fresh();
        $this->assertSame($listing->crop_name, $fresh->crop_name);
        $this->assertTrue($fresh->isPublished());
        $this->assertSame($version, $fresh->version);
    }

    public function test_the_owner_list_shows_only_the_farmers_own_listings(): void
    {
        $farmer = $this->farmer();
        CropListing::factory()->for($farmer, 'farmer')->create(['crop_name' => 'My onion']);
        CropListing::factory()->create(['crop_name' => 'Someone else’s rice']);

        $this->actingAs($farmer)->get(route('farmer.listings.index'))
            ->assertOk()
            ->assertSee('My onion')
            ->assertDontSee('Someone else’s rice');
    }

    public function test_an_unpublished_listing_and_its_photos_are_private(): void
    {
        $owner = $this->farmer();
        $listing = CropListing::factory()->for($owner, 'farmer')->unpublished()->withPhotos()->create();
        $photo = $listing->photos()->first();

        $this->get(route('marketplace.show', $listing))->assertNotFound();
        $this->get($photo->url('thumb'))->assertNotFound();
        $this->actingAs($this->farmer())->get($photo->url('display'))->assertNotFound();

        // The owner can still see their own hidden photo in the workspace.
        $this->actingAs($owner)->get($photo->url('display'))->assertOk();
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
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);

        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm())->assertStatus(419);
        $this->actingAs($farmer)->delete(route('farmer.listings.destroy', $listing), ['confirm_remove' => '1', 'version' => 1])->assertStatus(419);

        $this->assertSame(1, CropListing::count());
    }

    public function test_publishing_is_rate_limited_per_account(): void
    {
        $farmer = $this->farmer();

        // Invalid (empty) forms still count: the limit applies before any validation or photo work.
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($farmer)->post(route('farmer.listings.store'), [])->assertRedirect();
        }
        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm())->assertStatus(429);

        $this->assertSame(0, CropListing::count());
        $this->assertSame([], Storage::disk(CropListingPhotoService::DISK)->allFiles());
    }

    public function test_opening_the_remove_link_changes_nothing(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);

        $this->actingAs($farmer)->get(route('farmer.listings.show', [$listing, 'confirm' => 'remove']))
            ->assertOk()
            ->assertSee('Remove this crop listing?');

        $this->assertNotNull($listing->fresh());
    }

    public function test_farmer_text_is_shown_as_text_never_as_html(): void
    {
        $farmer = $this->farmer();
        $listing = CropListing::factory()->for($farmer, 'farmer')->withPhotos()->create([
            'crop_name' => '<script>alert(1)</script>Rice',
            'quality_description' => '<img src=x onerror=alert(2)>',
            'public_area' => '"><svg onload=alert(3)>',
        ]);

        foreach ([
            $this->get(route('marketplace.index')),
            $this->get(route('marketplace.show', $listing)),
            $this->actingAs($farmer)->get(route('farmer.listings.show', $listing)),
            $this->actingAs($farmer)->get(route('farmer.listings.edit', $listing)),
        ] as $response) {
            $response->assertOk()
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('<img src=x onerror', false)
                ->assertDontSee('<svg onload', false)
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;Rice', false);
        }
    }
}
