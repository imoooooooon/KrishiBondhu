<?php

namespace Tests\Feature\CropListings;

use App\Models\CropListing;
use App\Services\CropListingPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** F1: publishing a new crop listing, and the server-side checks before publication (spec §9, §13.1). */
class CreateCropListingTest extends TestCase
{
    use CropListingHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(CropListingPhotoService::DISK);
    }

    public function test_farmer_publishes_a_complete_listing(): void
    {
        $farmer = $this->farmer();

        $response = $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm());

        $listing = CropListing::firstOrFail();
        $response->assertRedirect(route('farmer.listings.show', $listing));
        $this->assertSame($farmer->id, $listing->farmer_id);
        $this->assertSame('published', $listing->status);
        $this->assertNotNull($listing->published_at);
        $this->assertSame(1, $listing->version);
        $this->assertSame('250.000', $listing->quantity);   // exact decimal strings, never floats
        $this->assertSame('35.50', $listing->price_per_unit);
        $this->assertSame(['pickup', 'farmer_arranged'], $listing->deliveryMethods());
        $this->assertNull($listing->delivery_notes);

        $photo = $listing->photos()->sole();
        $this->assertSame(1, $photo->position);
        Storage::disk(CropListingPhotoService::DISK)->assertExists([$photo->display_path, $photo->thumbnail_path]);

        $this->get(route('marketplace.show', $listing))->assertOk()->assertSee('Potato — Diamant');
    }

    public function test_owner_status_version_and_dates_cannot_be_sent_in(): void
    {
        $farmer = $this->farmer();
        $other = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm([
            'farmer_id' => $other->id,
            'status' => 'unpublished',
            'version' => 99,
            'published_at' => '2001-01-01 00:00:00',
            'deleted_at' => '2001-01-01 00:00:00',
        ]));

        $listing = CropListing::withTrashed()->firstOrFail();
        $this->assertSame($farmer->id, $listing->farmer_id);
        $this->assertSame('published', $listing->status);
        $this->assertSame(1, $listing->version);
        $this->assertNull($listing->deleted_at);
        $this->assertTrue($listing->published_at->isToday());
    }

    public function test_bangla_digits_are_stored_as_exact_numbers(): void
    {
        $this->actingAs($this->farmer())->post(route('farmer.listings.store'), $this->validForm([
            'quantity' => '১২৫০.৫',
            'price_per_unit' => '৩৫.৭৫',
            'crop_name' => 'আলু — ডায়মন্ড',
        ]))->assertSessionHasNoErrors();

        $listing = CropListing::firstOrFail();
        $this->assertSame('1250.500', $listing->quantity);
        $this->assertSame('35.75', $listing->price_per_unit);
        $this->assertSame('আলু — ডায়মন্ড', $listing->crop_name);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function invalidNumbers(): array
    {
        return [
            'zero price' => ['price_per_unit', '0'],
            'negative price' => ['price_per_unit', '-5'],
            'comma separator' => ['price_per_unit', '1,250'],
            'currency sign' => ['price_per_unit', '৳35'],
            'scientific notation' => ['price_per_unit', '1e3'],
            'three price decimals' => ['price_per_unit', '35.555'],
            'price above maximum' => ['price_per_unit', '1000000.01'],
            'not a number' => ['price_per_unit', 'NaN'],
            'zero quantity' => ['quantity', '0'],
            'four quantity decimals' => ['quantity', '1.2345'],
            'quantity above maximum' => ['quantity', '1000000.001'],
            'leading dot' => ['quantity', '.5'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers_are_rejected_not_rounded(string $field, string $value): void
    {
        $this->actingAs($this->farmer())
            ->post(route('farmer.listings.store'), $this->validForm([$field => $value]))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, CropListing::withTrashed()->count());
        $this->assertSame([], Storage::disk(CropListingPhotoService::DISK)->allFiles());
    }

    public function test_boundary_numbers_are_accepted_exactly(): void
    {
        $this->actingAs($this->farmer())->post(route('farmer.listings.store'), $this->validForm([
            'quantity' => '1000000',
            'price_per_unit' => '0.01',
        ]))->assertSessionHasNoErrors();

        $listing = CropListing::firstOrFail();
        $this->assertSame('1000000.000', $listing->quantity);
        $this->assertSame('0.01', $listing->price_per_unit);
    }

    public function test_pieces_must_be_whole_numbers(): void
    {
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm(['unit_code' => 'piece', 'quantity' => '2.5']))
            ->assertSessionHasErrors('quantity');
        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm(['unit_code' => 'piece', 'quantity' => '40.000']))
            ->assertSessionHasNoErrors();

        $this->assertSame('40.000', CropListing::sole()->quantity);
    }

    /** @return array<string, array{0: string}> */
    public static function requiredFields(): array
    {
        return array_map(fn ($field) => [$field], array_combine(
            $fields = ['crop_name', 'quality_description', 'harvest_date', 'quantity', 'unit_code', 'price_per_unit', 'public_area', 'delivery_options', 'photos', 'submission_token'],
            $fields,
        ));
    }

    #[DataProvider('requiredFields')]
    public function test_each_required_detail_is_checked_before_publication(string $field): void
    {
        $form = $this->validForm();
        unset($form[$field]);

        $this->actingAs($this->farmer())
            ->post(route('farmer.listings.store'), $form)
            ->assertSessionHasErrors($field);

        $this->assertSame(0, CropListing::withTrashed()->count());
    }

    public function test_text_limits_and_codes_are_checked(): void
    {
        $farmer = $this->farmer();
        $cases = [
            ['crop_name', 'A'],
            ['crop_name', str_repeat('আ', 101)],
            ['quality_description', 'Good'],
            ['public_area', str_repeat('x', 121)],
            ['delivery_notes', str_repeat('x', 301)],
            ['unit_code', 'maund'],
            ['delivery_options', ['pickup', 'helicopter']],
            ['delivery_options', ['pickup', 'pickup']],
            ['submission_token', 'not-a-uuid'],
        ];

        foreach ($cases as [$field, $value]) {
            $this->actingAs($farmer)
                ->post(route('farmer.listings.store'), $this->validForm([$field => $value]))
                ->assertSessionHasErrors();
        }

        $this->assertSame(0, CropListing::withTrashed()->count());
    }

    public function test_harvest_date_follows_the_bangladesh_calendar(): void
    {
        // 18:30 UTC on 13 Nov is already 00:30 on 14 Nov in Dhaka (UTC+6).
        Carbon::setTestNow(Carbon::parse('2026-11-13 18:30:00', 'UTC'));
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm(['harvest_date' => '2026-11-15']))
            ->assertSessionHasErrors('harvest_date');
        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm(['harvest_date' => '2026-11-14']))
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-11-14', CropListing::sole()->harvest_date->toDateString());
        Carbon::setTestNow();
    }

    public function test_the_same_form_sent_twice_publishes_once(): void
    {
        $farmer = $this->farmer();
        $form = $this->validForm();

        $first = $this->actingAs($farmer)->post(route('farmer.listings.store'), $form);
        $form['photos'] = [$this->photo()]; // a resend carries the files again
        $second = $this->actingAs($farmer)->post(route('farmer.listings.store'), $form);

        $listing = CropListing::sole();
        $first->assertRedirect(route('farmer.listings.show', $listing));
        $second->assertRedirect(route('farmer.listings.show', $listing))
            ->assertSessionHas('status', 'This listing was already published from your earlier submission. Nothing was published twice.');
        $this->assertSame(1, $listing->photos()->count());
        $this->assertCount(2, Storage::disk(CropListingPhotoService::DISK)->allFiles()); // only the first request's display + thumb
    }

    public function test_resending_the_form_of_a_removed_listing_does_not_bring_it_back(): void
    {
        $farmer = $this->farmer();
        $form = $this->validForm();
        $this->actingAs($farmer)->post(route('farmer.listings.store'), $form);
        $listing = CropListing::sole();
        $listing->delete();

        $form['photos'] = [$this->photo()];
        $this->actingAs($farmer)->post(route('farmer.listings.store'), $form)
            ->assertRedirect(route('farmer.listings.index'))
            ->assertSessionHas('listing_warning');

        $this->assertSame(0, CropListing::count());
        $this->assertSame(1, CropListing::withTrashed()->count());
    }

    public function test_the_same_token_from_two_farmers_makes_two_listings(): void
    {
        $form = $this->validForm();

        $this->actingAs($this->farmer())->post(route('farmer.listings.store'), $form);
        $form['photos'] = [$this->photo()];
        $this->actingAs($this->farmer())->post(route('farmer.listings.store'), $form);

        $this->assertSame(2, CropListing::count());
    }

    public function test_five_photos_are_kept_in_order_and_a_sixth_is_refused(): void
    {
        $farmer = $this->farmer();
        $five = array_map(fn ($i) => $this->photo("p{$i}.jpg"), range(1, 5));

        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm(['photos' => $five]))->assertSessionHasNoErrors();
        $this->assertSame([1, 2, 3, 4, 5], CropListing::sole()->photos->pluck('position')->all());

        $six = array_map(fn ($i) => $this->photo("q{$i}.jpg"), range(1, 6));
        $this->actingAs($farmer)->post(route('farmer.listings.store'), $this->validForm(['photos' => $six]))->assertSessionHasErrors('photos');
        $this->assertSame(1, CropListing::count());
    }

    public function test_unsafe_or_unsupported_files_are_refused_and_nothing_is_saved(): void
    {
        $farmer = $this->farmer();
        $files = [
            'php disguised as jpg' => UploadedFile::fake()->createWithContent('crop.jpg', '<?php echo "hi";'),
            'svg' => UploadedFile::fake()->createWithContent('crop.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'gif' => UploadedFile::fake()->image('crop.gif'),
            'pdf' => UploadedFile::fake()->create('crop.pdf', 10, 'application/pdf'),
            'over 5 MB' => UploadedFile::fake()->image('big.jpg')->size(5121),
            'too wide' => UploadedFile::fake()->image('wide.jpg', 6001, 10),
        ];

        foreach ($files as $case => $file) {
            $this->actingAs($farmer)
                ->post(route('farmer.listings.store'), $this->validForm(['photos' => [$this->photo(), $file]]))
                ->assertSessionHasErrors('photos.1');
        }

        $this->assertSame(0, CropListing::withTrashed()->count(), 'no listing for any refused file');
        $this->assertSame([], Storage::disk(CropListingPhotoService::DISK)->allFiles(), 'no files left behind');
    }

    public function test_a_broken_second_photo_removes_the_first_photos_files(): void
    {
        // Passes the header checks (a real JPEG start) but is cut off, so it cannot be decoded.
        $valid = $this->photo();
        $whole = $this->photo('whole.jpg', 800, 600); // keep a reference: the temp file is deleted with the object
        $truncated = substr(file_get_contents($whole->getRealPath()), 0, 600);

        $this->actingAs($this->farmer())->post(route('farmer.listings.store'), $this->validForm([
            'photos' => [$valid, UploadedFile::fake()->createWithContent('broken.jpg', $truncated)],
        ]))->assertSessionHasErrors('photos.1');

        $this->assertSame(0, CropListing::withTrashed()->count());
        $this->assertSame([], Storage::disk(CropListingPhotoService::DISK)->allFiles());
    }

    public function test_a_storage_failure_saves_nothing(): void
    {
        $disk = \Mockery::mock(Storage::disk(CropListingPhotoService::DISK))->makePartial();
        $disk->shouldReceive('put')->andReturnFalse();
        Storage::set(CropListingPhotoService::DISK, $disk);

        $this->withoutExceptionHandling();
        $this->expectException(\RuntimeException::class);

        try {
            $this->actingAs($this->farmer())->post(route('farmer.listings.store'), $this->validForm());
        } finally {
            $this->assertSame(0, CropListing::withTrashed()->count());
        }
    }

    public function test_a_database_failure_removes_the_new_photo_files(): void
    {
        // Simulate the database refusing the insert after the photos were already written.
        CropListing::creating(fn () => throw new \RuntimeException('database unavailable'));

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->farmer())->post(route('farmer.listings.store'), $this->validForm());
            $this->fail('The failure should reach the caller.');
        } catch (\RuntimeException $e) {
            $this->assertSame('database unavailable', $e->getMessage());
        }

        $this->assertSame([], Storage::disk(CropListingPhotoService::DISK)->allFiles());
    }

    public function test_the_form_keeps_text_and_shows_linked_errors_after_a_failure(): void
    {
        $farmer = $this->farmer();

        $this->actingAs($farmer)
            ->from(route('farmer.listings.create'))
            ->followingRedirects()
            ->post(route('farmer.listings.store'), $this->validForm(['crop_name' => 'Potato <b>x</b>', 'price_per_unit' => '1,250']))
            ->assertOk()
            ->assertSee('value="Potato &lt;b&gt;x&lt;/b&gt;"', false)
            ->assertSee('href="#field-price_per_unit"', false)
            ->assertSee('Please select your new photos again. Your text is still here.');
    }
}
