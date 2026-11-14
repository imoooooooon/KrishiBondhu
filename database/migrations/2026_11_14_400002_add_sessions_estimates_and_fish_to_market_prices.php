<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| F11 slice 2: prices that change during the day, tomorrow's estimate, and fish (agreed with Jarif, 2026-11-14).
| Spec: docs/features/F11-market-price-tracking.md §2.
|
| - price_session: vegetable and fish prices change from morning to evening, so an actual price belongs to a
|   session: morning | afternoon | evening. Existing rows become "morning".
| - is_estimate: a contributor may add their estimate for TOMORROW. It is stored with session "all_day" and is never
|   treated as an actual price. The CHECK ties the two: an estimate always has "all_day", an actual price never does.
| - The one-entry rule becomes one entry per contributor + market + item + type + day + session.
| - crop_code now also allows six fish (the column name stays; F12–F14 read it as "item").
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            $table->string('price_session', 16)->default('morning')->after('price_date');
            $table->boolean('is_estimate')->default(false)->after('price_session');
        });

        Schema::table('market_prices', function (Blueprint $table) {
            // The contributor foreign key uses this unique index, so add the new one before dropping the old one.
            $table->unique(
                ['contributor_id', 'market_id', 'crop_code', 'price_type', 'price_date', 'price_session'],
                'market_prices_one_per_session_unique',
            );
            $table->dropUnique('market_prices_one_per_day_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE market_prices
                DROP CHECK market_prices_crop_check,
                ADD CONSTRAINT market_prices_crop_check CHECK (crop_code IN (
                    'rice_coarse', 'rice_medium', 'rice_fine', 'wheat', 'lentil', 'potato', 'onion',
                    'garlic', 'ginger', 'green_chilli', 'tomato', 'brinjal', 'pumpkin', 'cucumber',
                    'fish_rui', 'fish_katla', 'fish_pangas', 'fish_tilapia', 'fish_hilsa', 'fish_prawn')),
                ADD CONSTRAINT market_prices_session_check CHECK (price_session IN ('morning', 'afternoon', 'evening', 'all_day')),
                ADD CONSTRAINT market_prices_estimate_check CHECK ((is_estimate = 1) = (price_session = 'all_day'))");
        }
    }

    /** Note: down() restores the crop-only list, so MySQL refuses it once any fish price has been recorded. */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE market_prices
                DROP CHECK market_prices_estimate_check,
                DROP CHECK market_prices_session_check,
                DROP CHECK market_prices_crop_check,
                ADD CONSTRAINT market_prices_crop_check CHECK (crop_code IN (
                    'rice_coarse', 'rice_medium', 'rice_fine', 'wheat', 'lentil', 'potato', 'onion',
                    'garlic', 'ginger', 'green_chilli', 'tomato', 'brinjal', 'pumpkin', 'cucumber'))");
        }

        Schema::table('market_prices', function (Blueprint $table) {
            $table->unique(['contributor_id', 'market_id', 'crop_code', 'price_type', 'price_date'], 'market_prices_one_per_day_unique');
            $table->dropUnique('market_prices_one_per_session_unique');
            $table->dropColumn(['price_session', 'is_estimate']);
        });
    }
};
