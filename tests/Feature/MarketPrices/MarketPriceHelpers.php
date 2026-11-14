<?php

namespace Tests\Feature\MarketPrices;

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Shared test data for the F11 tests. The markets migration inserts the real initial markets. */
trait MarketPriceHelpers
{
    protected function contributor(): User
    {
        return User::factory()->withRoles(Role::PRICE_CONTRIBUTOR)->create();
    }

    protected function userWith(string ...$roles): User
    {
        return User::factory()->withRoles(...$roles)->create();
    }

    protected function kawranBazar(): Market
    {
        return Market::where('name', 'Kawran Bazar')->firstOrFail();
    }

    /** A date relative to today in Bangladesh, as YYYY-MM-DD. */
    protected function daysFromToday(int $days): string
    {
        return Carbon::parse(MarketPrice::today())->addDays($days)->toDateString();
    }

    /** @return array<string, mixed> a complete, valid "Record a price" form */
    protected function validForm(array $overrides = []): array
    {
        return array_merge([
            'market_id' => $this->kawranBazar()->id,
            'crop_code' => 'potato',
            'price_type' => 'retail',
            'price_per_kg' => '35.50',
            'price_date' => MarketPrice::today(),
            'note' => 'Morning price, new harvest.',
        ], $overrides);
    }
}
