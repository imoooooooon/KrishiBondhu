<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Crop listings (F1). A farmer publishes one crop lot: what it is, how much, the price per unit,
| photos and the delivery arrangements they accept. Spec: docs/features/F1-crop-listings.md §12.
|
| - Money and quantities are DECIMAL (exact), never FLOAT.
| - Removing a listing sets deleted_at (soft delete); later features (orders) may still point at it.
| - status is only "published" or "unpublished". "Removed" is deleted_at, not a third status.
| - version goes up by one on every change, so a save from an old browser tab can be detected (409).
| - submission_token makes a double-clicked or re-sent "Publish" create only one listing.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_listings', function (Blueprint $table) {
            $table->id();
            // The owner. restrictOnDelete: an account with listings cannot be deleted by accident.
            $table->foreignId('farmer_id')->constrained('users')->restrictOnDelete();
            $table->string('crop_name', 100);
            $table->string('quality_description', 500);
            $table->decimal('quantity', 12, 3);        // amount offered, in unit_code
            $table->string('unit_code', 10);           // kg | tonne | piece
            $table->decimal('price_per_unit', 10, 2);  // taka per one unit_code
            $table->date('harvest_date');              // a calendar date, no time zone
            $table->string('public_area', 120);        // approximate area, never a street address
            $table->string('delivery_notes', 300)->nullable();
            $table->string('status', 16);              // published | unpublished
            $table->timestamp('published_at')->nullable(); // first publication; republishing keeps it
            $table->unsignedInteger('version')->default(1);
            $table->char('submission_token', 36);
            $table->timestamps();
            $table->softDeletes();

            // One listing per "Publish" form, also across removed listings.
            $table->unique(['farmer_id', 'submission_token']);
            // "My crop listings": newest edit first.
            $table->index(['farmer_id', 'deleted_at', 'updated_at', 'id'], 'crop_listings_owner_list_index');
            // Public marketplace: published, newest first.
            $table->index(['status', 'deleted_at', 'published_at', 'id'], 'crop_listings_public_list_index');
        });

        Schema::create('crop_listing_photos', function (Blueprint $table) {
            $table->id();
            // Rows cascade only if a listing is ever hard-deleted; files on disk are never deleted by the database.
            $table->foreignId('crop_listing_id')->constrained('crop_listings')->cascadeOnDelete();
            $table->unsignedInteger('position');       // lowest position = cover photo; gaps are fine
            $table->string('display_path');            // relative path on the private disk, made by the server
            $table->string('thumbnail_path');
            $table->unsignedSmallInteger('display_width');
            $table->unsignedSmallInteger('display_height');
            $table->unsignedSmallInteger('thumbnail_width');
            $table->unsignedSmallInteger('thumbnail_height');
            $table->timestamps();

            $table->unique(['crop_listing_id', 'position']);
        });

        Schema::create('crop_listing_delivery_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_listing_id')->constrained('crop_listings')->cascadeOnDelete();
            $table->string('method', 24);              // pickup | buyer_arranged | farmer_arranged | third_party

            $table->unique(['crop_listing_id', 'method']);
        });

        // The same rules as the Form Requests, so even a bug or a manual SQL edit cannot store impossible values.
        // MySQL 8.0.16+ enforces CHECK constraints. Other drivers (none used by this project) skip them.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE crop_listings
                ADD CONSTRAINT crop_listings_status_check CHECK (status IN ('published', 'unpublished')),
                ADD CONSTRAINT crop_listings_unit_check CHECK (unit_code IN ('kg', 'tonne', 'piece')),
                ADD CONSTRAINT crop_listings_quantity_check CHECK (quantity > 0 AND quantity <= 1000000),
                ADD CONSTRAINT crop_listings_piece_check CHECK (unit_code <> 'piece' OR quantity = FLOOR(quantity)),
                ADD CONSTRAINT crop_listings_price_check CHECK (price_per_unit > 0 AND price_per_unit <= 1000000),
                ADD CONSTRAINT crop_listings_version_check CHECK (version >= 1)");
            DB::statement("ALTER TABLE crop_listing_delivery_options
                ADD CONSTRAINT crop_listing_delivery_method_check
                CHECK (method IN ('pickup', 'buyer_arranged', 'farmer_arranged', 'third_party'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_listing_delivery_options');
        Schema::dropIfExists('crop_listing_photos');
        Schema::dropIfExists('crop_listings');
    }
};
