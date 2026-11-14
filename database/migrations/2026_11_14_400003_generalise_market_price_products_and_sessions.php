<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| F11: all farm produce, with the right unit and the right number of prices per day (agreed with Jarif, 2026-11-14).
| Spec: docs/features/F11-market-price-tracking.md §2 (D8–D10).
|
| 1. Products: farmers sell grains, vegetables, fish, meat, poultry, eggs, milk and oil. Milk and oil are sold per
|    litre and eggs per dozen, so "price per kg" no longer fits every product:
|      crop_code -> item_code, price_per_kg -> unit_price (taka per ONE unit; the unit is fixed by the product).
|    The unit is not stored: it comes from MarketPrice::ITEMS, so a row can never disagree with its product.
| 2. Sessions: only vegetables and fish change during the day (several times). Every other product has one price per
|    day, stored with session "all_day". Tomorrow's estimate also uses "all_day", so is_estimate joins the unique key.
|    The session CHECK below encodes exactly this rule.
| Existing actual prices of non-vegetable, non-fish products move to "all_day".
*/
return new class extends Migration
{
    /** Products whose price changes during the day (vegetables and fish). Same as MarketPrice's intraday groups. */
    private const INTRADAY = [
        'potato', 'onion', 'garlic', 'ginger', 'green_chilli', 'tomato', 'brinjal', 'pumpkin', 'cucumber', 'cauliflower', 'cabbage',
        'fish_rui', 'fish_katla', 'fish_pangas', 'fish_tilapia', 'fish_hilsa', 'fish_prawn',
    ];

    /** Products with one price per day. */
    private const DAILY = [
        'rice_coarse', 'rice_medium', 'rice_fine', 'wheat', 'maize', 'lentil',
        'beef', 'mutton', 'chicken_broiler', 'chicken_sonali', 'chicken_deshi', 'duck',
        'egg_chicken', 'egg_duck', 'milk_cow', 'oil_mustard', 'oil_soybean',
    ];

    /** The product list before this migration (for down()). */
    private const OLD_ITEMS = [
        'rice_coarse', 'rice_medium', 'rice_fine', 'wheat', 'lentil', 'potato', 'onion',
        'garlic', 'ginger', 'green_chilli', 'tomato', 'brinjal', 'pumpkin', 'cucumber',
        'fish_rui', 'fish_katla', 'fish_pangas', 'fish_tilapia', 'fish_hilsa', 'fish_prawn',
    ];

    public function up(): void
    {
        $mysql = DB::getDriverName() === 'mysql';

        if ($mysql) {
            DB::statement('ALTER TABLE market_prices
                DROP CHECK market_prices_crop_check, DROP CHECK market_prices_price_check,
                DROP CHECK market_prices_session_check, DROP CHECK market_prices_estimate_check');
        }

        Schema::table('market_prices', function (Blueprint $table) {
            $table->renameColumn('crop_code', 'item_code');
            $table->renameColumn('price_per_kg', 'unit_price');
        });

        // Products that do not change during the day have one price per day.
        DB::table('market_prices')->where('is_estimate', false)->whereNotIn('item_code', self::INTRADAY)
            ->update(['price_session' => 'all_day']);

        Schema::table('market_prices', function (Blueprint $table) {
            // The contributor foreign key uses the unique index, so add the new one before dropping the old one.
            $table->unique(
                ['contributor_id', 'market_id', 'item_code', 'price_type', 'price_date', 'price_session', 'is_estimate'],
                'market_prices_one_per_session_and_kind_unique',
            );
            $table->dropUnique('market_prices_one_per_session_unique');
        });

        if ($mysql) {
            DB::statement('ALTER TABLE market_prices
                ADD CONSTRAINT market_prices_item_check CHECK (item_code IN ('.$this->quoted([...self::INTRADAY, ...self::DAILY]).')),
                ADD CONSTRAINT market_prices_price_check CHECK (unit_price > 0 AND unit_price <= 100000),
                ADD CONSTRAINT market_prices_session_check CHECK (
                    (is_estimate = 1 AND price_session = \'all_day\')
                    OR (is_estimate = 0 AND item_code IN ('.$this->quoted(self::INTRADAY).')
                        AND price_session IN (\'morning\', \'afternoon\', \'evening\'))
                    OR (is_estimate = 0 AND item_code IN ('.$this->quoted(self::DAILY).') AND price_session = \'all_day\'))');
        }
    }

    /** Note: down() restores the older product list, so MySQL refuses it once any of the new products has a price. */
    public function down(): void
    {
        $mysql = DB::getDriverName() === 'mysql';

        if ($mysql) {
            DB::statement('ALTER TABLE market_prices
                DROP CHECK market_prices_item_check, DROP CHECK market_prices_price_check, DROP CHECK market_prices_session_check');
        }

        DB::table('market_prices')->where('is_estimate', false)->where('price_session', 'all_day')->update(['price_session' => 'morning']);

        Schema::table('market_prices', function (Blueprint $table) {
            $table->unique(['contributor_id', 'market_id', 'item_code', 'price_type', 'price_date', 'price_session'], 'market_prices_one_per_session_unique');
            $table->dropUnique('market_prices_one_per_session_and_kind_unique');
            $table->renameColumn('item_code', 'crop_code');
            $table->renameColumn('unit_price', 'price_per_kg');
        });

        if ($mysql) {
            DB::statement('ALTER TABLE market_prices
                ADD CONSTRAINT market_prices_crop_check CHECK (crop_code IN ('.$this->quoted(self::OLD_ITEMS).')),
                ADD CONSTRAINT market_prices_price_check CHECK (price_per_kg > 0 AND price_per_kg <= 100000),
                ADD CONSTRAINT market_prices_session_check CHECK (price_session IN (\'morning\', \'afternoon\', \'evening\', \'all_day\')),
                ADD CONSTRAINT market_prices_estimate_check CHECK ((is_estimate = 1) = (price_session = \'all_day\'))');
        }
    }

    /** @param  list<string>  $codes */
    private function quoted(array $codes): string
    {
        return implode(', ', array_map(fn (string $code) => "'".$code."'", $codes));
    }
};
