<?php

namespace Database\Factories;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use App\Models\User;
use App\Services\CropListingPhotoService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Test farm-supply products (F25). Default: a published fertilizer bag with stock, sold from Shibganj, Bogura
 * with buyer pickup, and NO bulk offers.
 *
 * Example: AgriculturalProduct::factory()->for($seller, 'seller')->unpublished()->withBulk()->create();
 *
 * @extends Factory<AgriculturalProduct>
 */
class AgriculturalProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->withRoles(Role::SELLER),
            'name' => fake()->randomElement(['Urea fertilizer', 'TSP fertilizer', 'MoP potash', 'Hybrid maize seed', 'Hand sprayer']),
            'brand' => null,
            'category' => 'fertilizer',
            'description' => 'Granular, 50 kg bag, sealed. Store dry.',
            'public_area' => 'Shibganj, Bogura',
            'delivery_notes' => null,
            'unit_code' => 'bag',
            'unit_price' => '1350.00',
            'stock_quantity' => '40.000',
            'bulk_min_quantity' => null,
            'bulk_min_unit_price' => null,
            'status' => AgriculturalProduct::STATUS_PUBLISHED,
            'published_at' => now(),
            'version' => 1,
            'submission_token' => (string) Str::uuid(),
        ];
    }

    /** Every product gets at least one delivery option (pickup) unless the test adds its own. */
    public function configure(): static
    {
        return $this->afterCreating(function (AgriculturalProduct $product) {
            if ($product->deliveryOptions()->doesntExist()) {
                $product->deliveryOptions()->create(['method' => 'pickup']);
            }
        });
    }

    /** Hidden from buyers. Never published, so published_at stays empty. */
    public function unpublished(): static
    {
        return $this->state(['status' => AgriculturalProduct::STATUS_UNPUBLISHED, 'published_at' => null]);
    }

    /** Accepts bulk offers from 10 units at no less than ৳ 1,250 per unit. */
    public function withBulk(string $minQuantity = '10.000', string $minPrice = '1250.00'): static
    {
        return $this->state(['bulk_min_quantity' => $minQuantity, 'bulk_min_unit_price' => $minPrice]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => '0.000']);
    }

    /**
     * A photo with real (tiny) JPEG files at the stored paths.
     * Call Storage::fake('local') first in tests, so nothing is written to your real storage folder.
     */
    public function withPhoto(): static
    {
        return $this->state(function () {
            $image = imagecreatetruecolor(16, 9);
            ob_start();
            imagejpeg($image);
            $bytes = ob_get_clean();

            $base = AgriculturalProduct::PHOTO_DIRECTORY.'/factory-'.Str::random(24);
            Storage::disk(CropListingPhotoService::DISK)->put($base.'-display.jpg', $bytes);
            Storage::disk(CropListingPhotoService::DISK)->put($base.'-thumb.jpg', $bytes);

            return [
                'photo_path' => $base.'-display.jpg',
                'photo_thumbnail_path' => $base.'-thumb.jpg',
                'photo_width' => 16,
                'photo_height' => 9,
            ];
        });
    }
}
