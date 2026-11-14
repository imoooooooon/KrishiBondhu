<?php

namespace Database\Factories;

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test price observations (F11). Default: today's retail potato price at a new test market, by a new contributor.
 *
 * Example: MarketPrice::factory()->for($contributor, 'contributor')->for($market)->create(['price_per_kg' => '42.50']);
 *
 * @extends Factory<MarketPrice>
 */
class MarketPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'market_id' => Market::factory(),
            'contributor_id' => User::factory()->withRoles(Role::PRICE_CONTRIBUTOR),
            'crop_code' => 'potato',
            'price_type' => 'retail',
            'price_per_kg' => '35.00',
            'price_date' => MarketPrice::today(),
            'note' => null,
            'version' => 1,
        ];
    }
}
