<?php

namespace Database\Factories;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Test farm-supply products (F25). Default: a published fertilizer bag with stock and NO bulk offers.
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
}
