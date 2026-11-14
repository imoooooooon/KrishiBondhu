<?php

namespace Database\Factories;

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test price observations (F11). Default: today's retail morning potato price at a new test market, by a new contributor.
 *
 * Example: MarketPrice::factory()->for($contributor, 'contributor')->for($market)->create(['unit_price' => '42.50']);
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
            'item_code' => 'potato',
            'price_type' => 'retail',
            'unit_price' => '35.00',
            'price_date' => MarketPrice::today(),
            'price_session' => 'morning',
            'is_estimate' => false,
            'note' => null,
            'version' => 1,
        ];
    }

    /** The contributor's estimate for tomorrow (session "all_day"). Keeps the factory's item (potato). */
    public function estimate(): static
    {
        return $this->state(['price_date' => MarketPrice::tomorrow(), 'price_session' => MarketPrice::DAY_SESSION, 'is_estimate' => true]);
    }

    /** An actual price for a product with one price per day (e.g. rice), session "all_day". */
    public function daily(string $itemCode = 'rice_coarse'): static
    {
        return $this->state(['item_code' => $itemCode, 'price_session' => MarketPrice::DAY_SESSION]);
    }
}
