<?php

namespace Database\Factories;

use App\Models\CropListing;
use App\Models\Role;
use App\Models\User;
use App\Services\CropListingPhotoService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Test crop listings (F1). Default: a published listing with one delivery option and NO photo.
 * Add ->withPhotos() for photo rows plus small real JPEG files on the photo disk
 * (call Storage::fake('local') first in tests, so nothing is written to your real storage folder).
 *
 * Example: CropListing::factory()->for($farmer, 'farmer')->unpublished()->withPhotos(2)->create();
 *
 * @extends Factory<CropListing>
 */
class CropListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'farmer_id' => User::factory()->withRoles(Role::FARMER),
            'crop_name' => fake()->randomElement(['Potato — Diamant', 'Aman rice (BR-11)', 'Red onion', 'Green chilli', 'Mustard seed']),
            'quality_description' => 'Medium size, sorted, no rot. Stored dry.',
            'quantity' => '250.000',
            'unit_code' => 'kg',
            'price_per_unit' => '35.00',
            'harvest_date' => Carbon::parse(CropListing::today())->subDays(3)->toDateString(),
            'public_area' => 'Shibganj, Bogura',
            'delivery_notes' => null,
            'status' => CropListing::STATUS_PUBLISHED,
            'published_at' => now(),
            'version' => 1,
            'submission_token' => (string) Str::uuid(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (CropListing $listing) {
            if ($listing->deliveryOptions()->doesntExist()) {
                $listing->deliveryOptions()->create(['method' => 'pickup']);
            }
        });
    }

    /** Hidden from buyers. Never published, so published_at stays empty. */
    public function unpublished(): static
    {
        return $this->state(['status' => CropListing::STATUS_UNPUBLISHED, 'published_at' => null]);
    }

    /** Photo rows with real (tiny) JPEG files at the stored paths. */
    public function withPhotos(int $count = 1): static
    {
        return $this->afterCreating(function (CropListing $listing) use ($count) {
            $image = imagecreatetruecolor(16, 10);
            ob_start();
            imagejpeg($image);
            $bytes = ob_get_clean();

            for ($i = 1; $i <= $count; $i++) {
                $base = CropListingPhotoService::DIRECTORY.'/factory-'.Str::random(24);
                Storage::disk(CropListingPhotoService::DISK)->put($base.'-display.jpg', $bytes);
                Storage::disk(CropListingPhotoService::DISK)->put($base.'-thumb.jpg', $bytes);
                $listing->photos()->create([
                    'position' => $i,
                    'display_path' => $base.'-display.jpg',
                    'thumbnail_path' => $base.'-thumb.jpg',
                    'display_width' => 16, 'display_height' => 10,
                    'thumbnail_width' => 16, 'thumbnail_height' => 10,
                ]);
            }
        });
    }
}
