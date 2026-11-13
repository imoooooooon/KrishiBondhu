<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Agricultural products (F25): fertilizer, pesticide, seed, tools and other farm supplies that a seller offers.
| Spec: docs/features/F25-agricultural-product-sales.md.
|
| - Money and stock are DECIMAL (exact), never FLOAT.
| - Removing a product sets deleted_at (soft delete): future order items (F5) and offers (F3) will point at it.
| - status is only "published" or "unpublished". "Removed" is deleted_at, not a third status.
| - version goes up by one on every change, so a save from an old browser tab can be detected (409).
| - submission_token makes a double-clicked or re-sent "Add product" create only one product.
| - Bulk offers: bulk_min_quantity is the public threshold (from this quantity a buyer may offer a price);
|   bulk_min_unit_price is the seller's PRIVATE lowest acceptable price. Both are empty when the seller
|   does not accept bulk offers. F3 will read these two columns; it does not own them.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agricultural_products', function (Blueprint $table) {
            $table->id();
            // The owner. restrictOnDelete: an account with products (and later, orders) cannot be deleted by accident.
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('brand', 80)->nullable();
            $table->string('category', 16);                       // fertilizer | pesticide | seed | tool | other
            $table->string('description', 1000);
            $table->string('unit_code', 10);                      // kg | litre | bag | packet | bottle | piece
            $table->decimal('unit_price', 10, 2);                 // taka per one unit_code (fixed price)
            $table->decimal('stock_quantity', 12, 3);             // available stock, in unit_code; 0 = out of stock
            $table->decimal('bulk_min_quantity', 12, 3)->nullable();  // public: bulk offers from this quantity
            $table->decimal('bulk_min_unit_price', 10, 2)->nullable(); // private: lowest price per unit the seller accepts
            $table->string('status', 16);                         // published | unpublished
            $table->timestamp('published_at')->nullable();        // first publication; republishing keeps it
            $table->unsignedInteger('version')->default(1);
            $table->char('submission_token', 36);
            $table->timestamps();
            $table->softDeletes();

            // One product per "Add product" form, also across removed products.
            $table->unique(['seller_id', 'submission_token']);
            // "My products": newest edit first.
            $table->index(['seller_id', 'deleted_at', 'updated_at', 'id'], 'agricultural_products_owner_list_index');
            // Public shop (slice 2): published, newest first; and the category filter.
            $table->index(['status', 'deleted_at', 'published_at', 'id'], 'agricultural_products_public_list_index');
            $table->index(['category', 'status', 'deleted_at'], 'agricultural_products_category_index');
        });

        // The same rules as the Form Requests, so even a bug or a manual SQL edit cannot store impossible values.
        // MySQL 8.0.16+ enforces CHECK constraints. Other drivers (none used by this project) skip them.
        // A CHECK that evaluates to NULL ("unknown") counts as passed, so the bulk rule says IS NOT NULL explicitly:
        // without it, a threshold with an empty minimum price would slip through.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agricultural_products
                ADD CONSTRAINT agricultural_products_status_check CHECK (status IN ('published', 'unpublished')),
                ADD CONSTRAINT agricultural_products_category_check CHECK (category IN ('fertilizer', 'pesticide', 'seed', 'tool', 'other')),
                ADD CONSTRAINT agricultural_products_unit_check CHECK (unit_code IN ('kg', 'litre', 'bag', 'packet', 'bottle', 'piece')),
                ADD CONSTRAINT agricultural_products_price_check CHECK (unit_price > 0 AND unit_price <= 1000000),
                ADD CONSTRAINT agricultural_products_stock_check CHECK (stock_quantity >= 0 AND stock_quantity <= 1000000),
                ADD CONSTRAINT agricultural_products_whole_stock_check
                    CHECK (unit_code NOT IN ('bag', 'packet', 'bottle', 'piece') OR stock_quantity = FLOOR(stock_quantity)),
                ADD CONSTRAINT agricultural_products_bulk_check CHECK (
                    (bulk_min_quantity IS NULL AND bulk_min_unit_price IS NULL)
                    OR (bulk_min_quantity IS NOT NULL AND bulk_min_unit_price IS NOT NULL
                        AND bulk_min_quantity >= 2 AND bulk_min_quantity <= 1000000
                        AND bulk_min_unit_price > 0 AND bulk_min_unit_price <= unit_price)),
                ADD CONSTRAINT agricultural_products_whole_bulk_check
                    CHECK (bulk_min_quantity IS NULL OR unit_code NOT IN ('bag', 'packet', 'bottle', 'piece')
                        OR bulk_min_quantity = FLOOR(bulk_min_quantity)),
                ADD CONSTRAINT agricultural_products_version_check CHECK (version >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agricultural_products');
    }
};
