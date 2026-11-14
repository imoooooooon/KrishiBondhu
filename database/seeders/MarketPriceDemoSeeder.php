<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Role;
use App\Services\MarketPriceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data for F11 (market price tracking), for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=MarketPriceDemoSeeder
 *
 * Two demo price contributors record a week of prices in three markets, plus tomorrow's estimates. Vegetables and
 * fish get a morning and an evening price today. One entry is corrected and one is withdrawn, so the history and
 * "corrected" states can be shown. Prices are made-up but realistic Taka amounts, not real market data.
 * Every price goes through MarketPriceService::record, the same path as the contributor form.
 * Password for every account: "password". Safe to run twice: nothing is added if a demo contributor has prices.
 */
class MarketPriceDemoSeeder extends Seeder
{
    use DemoAccounts;

    /** item code => [usual retail price in Taka, wholesale price or null]. */
    private const BASE_PRICES = [
        'rice_coarse' => [54, 50], 'rice_medium' => [62, 58], 'rice_fine' => [78, 72], 'lentil' => [110, 102],
        'potato' => [28, 24], 'onion' => [65, 58], 'green_chilli' => [120, 100], 'tomato' => [70, 60],
        'cauliflower' => [45, null], 'brinjal' => [60, null],
        'fish_rui' => [340, null], 'fish_hilsa' => [1400, null], 'fish_tilapia' => [220, null],
        'chicken_broiler' => [185, 175], 'chicken_sonali' => [290, null], 'egg_chicken' => [145, 138],
        'milk_cow' => [90, null], 'oil_soybean' => [175, 168], 'beef' => [780, null],
    ];

    public function run(MarketPriceService $prices): void
    {
        if ($this->refuseInProduction()) {
            return;
        }

        $jamal = $this->demoUser('01700000010', 'Jamal Uddin (demo price contributor)', [Role::PRICE_CONTRIBUTOR]);
        $rina = $this->demoUser('01700000011', 'Rina Begum (demo price contributor)', [Role::PRICE_CONTRIBUTOR]);

        if (MarketPrice::withTrashed()->whereIn('contributor_id', [$jamal->id, $rina->id])->exists()) {
            $this->command?->info('Demo market prices already exist. Nothing was added.');

            return;
        }

        // Each market's prices differ a little: Dhaka is dearer, the northern haats are cheaper.
        $markets = [
            ['Kawran Bazar', $jamal, 1.00],
            ['Jatrabari', $jamal, 0.97],
            ['Mohasthan Hat', $rina, 0.90],
        ];

        $today = Carbon::parse(MarketPrice::today());
        $made = 0;

        foreach ($markets as $m => [$marketName, $contributor, $factor]) {
            $market = Market::where('name', $marketName)->first();
            if (! $market) {
                $this->command?->warn("Market \"{$marketName}\" not found. Run the migrations first.");

                continue;
            }

            foreach (self::BASE_PRICES as $item => [$retail, $wholesale]) {
                $intraday = in_array(MarketPrice::ITEMS[$item][0], MarketPrice::INTRADAY_GROUPS, true);

                for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
                    $date = $today->copy()->subDays($daysAgo);
                    $sessions = ! $intraday ? [MarketPrice::DAY_SESSION] : ($daysAgo === 0 ? ['morning', 'evening'] : ['morning']);

                    foreach ($sessions as $s => $session) {
                        foreach (array_filter(['retail' => $retail, 'wholesale' => $wholesale]) as $type => $base) {
                            $amount = $this->price($base * $factor, $item, $daysAgo, $m) - ($s * $base * 0.04); // evening is cheaper
                            $made += $this->record($prices, $contributor, $market, $item, $type, $amount, $date, $session, false, $daysAgo);
                        }
                    }
                }

                // Tomorrow's estimate (retail only), shown apart from the actual prices.
                $estimate = $this->price($retail * $factor, $item, -1, $m);
                $made += $this->record($prices, $contributor, $market, $item, 'retail', $estimate, $today->copy()->addDay(), MarketPrice::DAY_SESSION, true, 0);
            }
        }

        $this->showCorrectionAndWithdrawal($prices, $jamal);

        $this->command?->info("Added {$made} demo market prices in 3 markets.");
        $this->command?->info('Contributors: 01700000010 (Jamal, Kawran Bazar and Jatrabari), 01700000011 (Rina, Mohasthan Hat). Password: "password".');
    }

    /** A steady, repeatable day-to-day change of up to about ±6%, rounded to whole Taka. */
    private function price(float $base, string $item, int $daysAgo, int $market): float
    {
        $wave = sin(crc32($item) % 100 + $daysAgo * 1.3 + $market) * 0.06;

        return round($base * (1 + $wave));
    }

    private function record(MarketPriceService $prices, $contributor, Market $market, string $item, string $type, float $amount,
        Carbon $date, string $session, bool $estimate, int $daysAgo): int
    {
        [$price, $created] = $prices->record($contributor, [
            'market_id' => $market->id, 'item_code' => $item, 'price_type' => $type,
            'unit_price' => number_format(max(1, round($amount)), 2, '.', ''),
            'price_date' => $date->toDateString(), 'price_session' => $session, 'is_estimate' => $estimate, 'note' => null,
        ]);

        // Record the entry on the day it applied, so "last updated" reads naturally. Inside the 7-day correction window.
        $hour = ['morning' => 8, 'afternoon' => 13, 'evening' => 18][$session] ?? 10;
        $at = Carbon::parse(MarketPrice::today(), MarketPrice::TIMEZONE)->subDays($daysAgo)->setTime($hour, 15)->utc();
        if ($at->isFuture()) {
            $at = now();
        }
        MarketPrice::whereKey($price->id)->toBase()->update(['created_at' => $at, 'updated_at' => $at]);

        return $created ? 1 : 0;
    }

    /** Correct one of today's prices (logged with a reason) and withdraw another (hidden, kept with a reason). */
    private function showCorrectionAndWithdrawal(MarketPriceService $prices, $jamal): void
    {
        $mine = MarketPrice::recordedBy($jamal)->where('price_date', MarketPrice::today())->where('is_estimate', false)->where('price_type', 'retail');

        $onion = (clone $mine)->where('item_code', 'onion')->where('price_session', 'morning')->first();
        if ($onion) {
            $prices->correct($onion, $onion->version, ['unit_price' => number_format((float) $onion->unit_price + 5, 2, '.', ''), 'note' => 'Checked again with three shops.'],
                'Typed the wholesale price by mistake.', $jamal);
        }

        $beef = (clone $mine)->where('item_code', 'beef')->first();
        if ($beef) {
            $prices->withdraw($beef, $beef->version, 'Recorded for the wrong market.');
        }
    }
}
