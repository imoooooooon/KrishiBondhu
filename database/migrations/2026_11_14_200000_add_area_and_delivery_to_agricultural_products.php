<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| F25: where a product is sold from and how it can reach the buyer (same idea as F1 crop listings).
|
| - public_area: an approximate area such as "Shibganj, Bogura", never a street address. Anyone can see it.
|   Nullable in the database only so products saved before this migration stay valid; the forms require it.
| - agricultural_product_delivery_options: the delivery arrangements the seller accepts, one row per method.
|   The codes mirror F1's (farmer_arranged becomes seller_arranged), so F4 (delivery cost) can treat crops
|   and supplies the same way. No fee is calculated here: that is F4.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agricultural_products', function (Blueprint $table) {
            $table->string('public_area', 120)->nullable()->after('description');
            $table->string('delivery_notes', 300)->nullable()->after('public_area');
        });

        Schema::create('agricultural_product_delivery_options', function (Blueprint $table) {
            $table->id();
            // Rows cascade only if a product is ever hard-deleted; normal removal is a soft delete.
            // Short explicit key name: the automatic one would be 70 characters, over MySQL's 64-character limit.
            $table->foreignId('agricultural_product_id')
                ->constrained('agricultural_products', 'id', 'agricultural_product_delivery_product_fk')->cascadeOnDelete();
            $table->string('method', 24); // pickup | buyer_arranged | seller_arranged | third_party

            $table->unique(['agricultural_product_id', 'method'], 'agricultural_product_delivery_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agricultural_product_delivery_options
                ADD CONSTRAINT agricultural_product_delivery_method_check
                CHECK (method IN ('pickup', 'buyer_arranged', 'seller_arranged', 'third_party'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agricultural_product_delivery_options');

        Schema::table('agricultural_products', function (Blueprint $table) {
            $table->dropColumn(['public_area', 'delivery_notes']);
        });
    }
};
