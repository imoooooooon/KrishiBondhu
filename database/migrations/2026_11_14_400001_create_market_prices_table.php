<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Market price observations (F11). One row = one contributor saw one price for one crop at one market on one day.
| Spec: docs/features/F11-market-price-tracking.md.
|
| - The price is DECIMAL (exact), never FLOAT, and always per kilogram.
| - History is kept: a new price is always a new row; rows are never deleted (no delete route, restrict FKs).
| - One row per contributor + market + crop + price type + day (unique key): a resent form cannot add a second
|   row, and two contributors can still report the same crop independently.
| - version goes up by one on every correction (slice 2), so a correction from an old tab can be detected (409).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: a market or a contributor with recorded prices cannot be deleted by accident.
            $table->foreignId('market_id')->constrained('markets')->restrictOnDelete();
            $table->foreignId('contributor_id')->constrained('users')->restrictOnDelete();
            $table->string('crop_code', 24);                   // one of MarketPrice::CROPS
            $table->string('price_type', 16);                  // retail | wholesale
            $table->decimal('price_per_kg', 8, 2);             // taka per kilogram
            $table->date('price_date');                        // the day the price applied (Bangladesh calendar)
            $table->string('note', 200)->nullable();           // e.g. "morning, grade A"
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['contributor_id', 'market_id', 'crop_code', 'price_type', 'price_date'], 'market_prices_one_per_day_unique');
            // Latest price per market + crop + type, and the recent entries for one of them.
            $table->index(['market_id', 'crop_code', 'price_type', 'price_date', 'id'], 'market_prices_latest_index');
            // "My prices": newest change first.
            $table->index(['contributor_id', 'updated_at', 'id'], 'market_prices_contributor_index');
        });

        // The same rules as the Form Requests, so even a bug or a manual SQL edit cannot store impossible values.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE market_prices
                ADD CONSTRAINT market_prices_crop_check CHECK (crop_code IN (
                    'rice_coarse', 'rice_medium', 'rice_fine', 'wheat', 'lentil', 'potato', 'onion',
                    'garlic', 'ginger', 'green_chilli', 'tomato', 'brinjal', 'pumpkin', 'cucumber')),
                ADD CONSTRAINT market_prices_type_check CHECK (price_type IN ('retail', 'wholesale')),
                ADD CONSTRAINT market_prices_price_check CHECK (price_per_kg > 0 AND price_per_kg <= 100000),
                ADD CONSTRAINT market_prices_version_check CHECK (version >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
