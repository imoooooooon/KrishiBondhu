<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| F11 slice 4: corrections and withdrawal, keeping history (agreed with Jarif, 2026-11-14).
| Spec: docs/features/F11-market-price-tracking.md §3b.
|
| - Withdraw ("delete") hides an entry everywhere but keeps the row: deleted_at (Laravel soft delete) and a required
|   withdrawal_reason. A CHECK makes them come together.
| - active_key is 1 for a live entry and NULL for a withdrawn one (a stored generated column). The unique key includes
|   it, and MySQL lets NULLs repeat, so a withdrawn entry no longer blocks recording the right price for that slot.
| - market_price_revisions: every correction stores the values it replaced and the reason. Rows are only ever added.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('withdrawal_reason', 200)->nullable();
            $table->unsignedTinyInteger('active_key')->nullable()->storedAs('IF(deleted_at IS NULL, 1, NULL)');
        });

        Schema::table('market_prices', function (Blueprint $table) {
            // The contributor foreign key uses the unique index, so add the new one before dropping the old one.
            $table->unique(
                ['contributor_id', 'market_id', 'item_code', 'price_type', 'price_date', 'price_session', 'is_estimate', 'active_key'],
                'market_prices_one_live_entry_unique',
            );
            $table->dropUnique('market_prices_one_per_session_and_kind_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE market_prices ADD CONSTRAINT market_prices_withdrawal_check
                CHECK ((deleted_at IS NULL) = (withdrawal_reason IS NULL))');
        }

        Schema::create('market_price_revisions', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: a price with corrections cannot be erased by accident (prices are never hard-deleted).
            $table->foreignId('market_price_id')->constrained('market_prices')->restrictOnDelete();
            $table->foreignId('revised_by')->constrained('users')->restrictOnDelete();
            $table->decimal('old_unit_price', 8, 2);
            $table->string('old_note', 200)->nullable();
            $table->string('reason', 200);
            $table->timestamp('created_at')->useCurrent(); // revisions are never updated, so no updated_at

            $table->index(['market_price_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_price_revisions');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE market_prices DROP CHECK market_prices_withdrawal_check');
        }

        Schema::table('market_prices', function (Blueprint $table) {
            $table->unique(
                ['contributor_id', 'market_id', 'item_code', 'price_type', 'price_date', 'price_session', 'is_estimate'],
                'market_prices_one_per_session_and_kind_unique',
            );
            $table->dropUnique('market_prices_one_live_entry_unique');
        });

        Schema::table('market_prices', function (Blueprint $table) {
            $table->dropColumn(['active_key', 'withdrawal_reason']);
            $table->dropSoftDeletes();
        });
    }
};
