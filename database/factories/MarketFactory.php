<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test markets. The real initial markets come from the markets migration; this makes extra ones with unique names.
 *
 * @extends Factory<Market>
 */
class MarketFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Test Hat '.fake()->unique()->numberBetween(1, 99999);

        return [
            'name' => $name,
            'name_bn' => 'পরীক্ষা হাট '.fake()->unique()->numberBetween(1, 99999),
            'upazila' => 'Shibganj',
            'district' => 'Bogura',
        ];
    }
}
