<?php

namespace Tests\Feature\CropListings;

use App\Models\CropListing;
use App\Services\CropListingPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F1: editing, photo changes, hiding and publishing again, removal, and stale tabs (spec §7, §13.2, §13.3). */
class ManageCropListingTest extends TestCase
{
    use CropListingHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(CropListingPhotoService::DISK);
    }

    private function disk()
    {
        return Storage::disk(CropListingPhotoService::DISK);
    }

    public function test_editing_text_keeps_photos_and_status_and_raises_the_version(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer, photos: 2);
        $photoIds = $listing->photos->pluck('id')->all();

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, [
            'quantity' => '180.5',
            'price_per_unit' => '38',
            'delivery_options' => ['buyer_arranged'],
        ]))->assertRedirect(route('farmer.listings.show', $listing))->assertSessionHasNoErrors();

        $fresh = $listing->fresh(['photos', 'deliveryOptions']);
        $this->assertSame('180.500', $fresh->quantity);
        $this->assertSame('38.00', $fresh->price_per_unit);
        $this->assertSame(['buyer_arranged'], $fresh->deliveryMethods());
        $this->assertSame($photoIds, $fresh->photos->pluck('id')->all());
        $this->assertTrue($fresh->isPublished());
        $this->assertSame(2, $fresh->version);
    }

    public function test_editing_an_unpublished_listing_keeps_it_hidden(): void
    {
        $farmer = $this->farmer();
        $listing = CropListing::factory()->for($farmer, 'farmer')->unpublished()->withPhotos()->create();

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, ['crop_name' => 'Red onion']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($listing->fresh()->isPublished());
    }

    public function test_removing_and_adding_photos_and_old_files_are_deleted_after_saving(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer, photos: 2);
        [$first, $second] = $listing->photos->all();

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, [
            'remove_photo_ids' => [$first->id],
            'photos' => [$this->photo('new.jpg')],
        ]))->assertSessionHasNoErrors();

        $photos = $listing->fresh()->photos;
        $this->assertSame([$second->id], $photos->take(1)->pluck('id')->all(), 'the kept photo becomes the cover');
        $this->assertSame([2, 3], $photos->pluck('position')->all(), 'the new photo goes after the kept one');
        $this->disk()->assertMissing([$first->display_path, $first->thumbnail_path]);
        $this->disk()->assertExists([$second->display_path, $photos[1]->display_path, $photos[1]->thumbnail_path]);
    }

    public function test_replacing_every_photo_with_a_new_one_works(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);
        $old = $listing->photos->first();

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, [
            'remove_photo_ids' => [$old->id],
            'photos' => [$this->photo()],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, $listing->photos()->count());
        $this->assertNotSame($old->id, $listing->photos()->first()->id);
    }

    public function test_removing_every_photo_without_a_replacement_is_refused(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);
        $photo = $listing->photos->first();

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, [
            'crop_name' => 'Changed name',
            'remove_photo_ids' => [$photo->id],
        ]))->assertSessionHasErrors('photos');

        $fresh = $listing->fresh();
        $this->assertNotSame('Changed name', $fresh->crop_name, 'the whole update is refused');
        $this->assertSame(1, $fresh->version);
        $this->disk()->assertExists($photo->display_path);
    }

    public function test_more_than_five_photos_in_total_is_refused(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer, photos: 4);

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, [
            'photos' => [$this->photo('a.jpg'), $this->photo('b.jpg')],
        ]))->assertSessionHasErrors('photos');

        $this->assertSame(4, $listing->photos()->count());
        $this->assertCount(8, $this->disk()->allFiles(), 'no new files were left behind');
    }

    public function test_a_photo_of_another_listing_cannot_be_removed_through_this_one(): void
    {
        $farmer = $this->farmer();
        $mine = $this->listingFor($farmer, photos: 2);
        $theirs = $this->listingFor($this->farmer());
        $foreign = $theirs->photos->first();

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $mine), $this->editForm($mine, [
            'remove_photo_ids' => [$foreign->id],
        ]))->assertSessionHasErrors('remove_photo_ids');

        $this->assertNotNull($foreign->fresh());
        $this->disk()->assertExists($foreign->display_path);
        $this->assertSame(2, $mine->photos()->count());
        $this->assertSame(1, $mine->fresh()->version);
    }

    public function test_duplicate_photo_ids_are_refused(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer, photos: 2);
        $id = $listing->photos->first()->id;

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $this->editForm($listing, [
            'remove_photo_ids' => [$id, $id],
        ]))->assertSessionHasErrors('remove_photo_ids.0');

        $this->assertSame(2, $listing->photos()->count());
    }

    public function test_a_save_from_an_old_tab_is_refused_with_a_conflict_page(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);
        $tabA = $this->editForm($listing, ['crop_name' => 'Saved first']);
        $tabB = $this->editForm($listing, ['crop_name' => 'Old tab text', 'photos' => [$this->photo()]]);

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $tabA)->assertSessionHasNoErrors();
        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), $tabB)
            ->assertStatus(409)
            ->assertSee('Your changes were not saved.')
            ->assertSee('Saved first')
            ->assertSee('Old tab text');

        $fresh = $listing->fresh();
        $this->assertSame('Saved first', $fresh->crop_name);
        $this->assertSame(2, $fresh->version);
        $this->assertSame(1, $fresh->photos()->count());
        $this->assertCount(2, $this->disk()->allFiles(), 'the stale request’s new photo files were deleted');
    }

    public function test_hiding_a_listing_revokes_its_public_page_and_photo_links(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);
        $photo = $listing->photos->first();
        $firstPublished = $listing->published_at->toDateTimeString();

        $this->get(route('marketplace.show', $listing))->assertOk();
        $this->get($photo->url('display'))->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), ['operation' => 'unpublish', 'version' => 1])
            ->assertRedirect(route('farmer.listings.show', $listing));
        auth()->logout();

        $this->get(route('marketplace.show', $listing))->assertNotFound();
        $this->get($photo->url('display'))->assertNotFound();
        $this->get(route('marketplace.index'))->assertDontSee($listing->crop_name);

        Carbon::setTestNow(now()->addDay());
        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), ['operation' => 'publish', 'version' => 2])
            ->assertSessionHasNoErrors();
        auth()->logout();
        Carbon::setTestNow();

        $this->get($photo->url('display'))->assertOk();
        $fresh = $listing->fresh();
        $this->assertTrue($fresh->isPublished());
        $this->assertSame(3, $fresh->version);
        $this->assertSame($firstPublished, $fresh->published_at->toDateTimeString(), 'republishing keeps the first publication time');
    }

    public function test_publishing_again_is_refused_when_the_photo_files_are_missing(): void
    {
        $farmer = $this->farmer();
        $listing = CropListing::factory()->for($farmer, 'farmer')->unpublished()->withPhotos()->create();
        $this->disk()->delete($listing->photos()->first()->display_path);

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), ['operation' => 'publish', 'version' => 1])
            ->assertSessionHasErrors('operation');

        $this->assertFalse($listing->fresh()->isPublished());
        $this->assertSame(1, $listing->fresh()->version);
    }

    public function test_unknown_operations_are_refused(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);

        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), ['operation' => 'approve', 'version' => 1])
            ->assertSessionHasErrors('operation');
        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), ['operation' => 'unpublish'])
            ->assertSessionHasErrors('version');

        $this->assertTrue($listing->fresh()->isPublished());
    }

    public function test_removal_needs_the_confirmation_and_the_current_version(): void
    {
        $farmer = $this->farmer();
        $listing = $this->listingFor($farmer);
        $photo = $listing->photos->first();

        $this->actingAs($farmer)->delete(route('farmer.listings.destroy', $listing), ['version' => 1])
            ->assertSessionHasErrors('confirm_remove');
        $this->actingAs($farmer)->delete(route('farmer.listings.destroy', $listing), ['confirm_remove' => '1', 'version' => 7])
            ->assertStatus(409);
        $this->assertNotNull($listing->fresh());

        $this->actingAs($farmer)->delete(route('farmer.listings.destroy', $listing), ['confirm_remove' => '1', 'version' => 1])
            ->assertRedirect(route('farmer.listings.index'));

        $removed = CropListing::withTrashed()->find($listing->id);
        $this->assertTrue($removed->trashed());
        $this->assertSame(2, $removed->version);
        $this->disk()->assertExists($photo->display_path); // soft removal keeps the private file

        // Gone from every normal route, including the old photo link.
        $this->actingAs($farmer)->get(route('farmer.listings.show', $listing))->assertNotFound();
        $this->actingAs($farmer)->get(route('farmer.listings.edit', $listing))->assertNotFound();
        $this->actingAs($farmer)->get($photo->url('thumb'))->assertNotFound();
        $this->actingAs($farmer)->get(route('farmer.listings.index'))->assertDontSee($listing->crop_name);
        $this->get(route('marketplace.show', $listing))->assertNotFound();
        $this->actingAs($farmer)->patch(route('farmer.listings.update', $listing), ['operation' => 'publish', 'version' => 2])->assertNotFound();
    }

    public function test_status_filter_on_the_owner_list(): void
    {
        $farmer = $this->farmer();
        CropListing::factory()->for($farmer, 'farmer')->create(['crop_name' => 'Visible potato']);
        CropListing::factory()->for($farmer, 'farmer')->unpublished()->create(['crop_name' => 'Hidden onion']);

        $this->actingAs($farmer)->get(route('farmer.listings.index', ['status' => 'unpublished']))
            ->assertOk()->assertSee('Hidden onion')->assertDontSee('Visible potato');
        $this->actingAs($farmer)->get(route('farmer.listings.index', ['status' => 'published']))
            ->assertOk()->assertSee('Visible potato')->assertDontSee('Hidden onion');
        $this->actingAs($farmer)->get(route('farmer.listings.index', ['status' => 'drop table']))
            ->assertOk()->assertSee('That filter does not exist')->assertSee('Visible potato')->assertSee('Hidden onion');
    }

    public function test_the_edit_form_shows_exact_saved_values(): void
    {
        $farmer = $this->farmer();
        $listing = CropListing::factory()->for($farmer, 'farmer')->withPhotos()->create(['quantity' => '12.125', 'price_per_unit' => '35.50', 'unit_code' => 'tonne']);

        $this->actingAs($farmer)->get(route('farmer.listings.edit', $listing))
            ->assertOk()
            ->assertSee('value="12.125"', false)
            ->assertSee('value="35.5"', false)
            ->assertSee('<option value="tonne" selected>', false)
            ->assertSee('name="version" value="1"', false);
    }
}
