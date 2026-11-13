<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| F25: one optional photo per product (same approach as F6 cattle photos).
| The upload is re-encoded by CropListingPhotoService into a display JPEG and a thumbnail on the PRIVATE disk,
| folder "agricultural-product-photos". Files are only sent through AgriculturalProductPhotoController,
| which checks the product's visibility on every request.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agricultural_products', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('bulk_min_unit_price');   // relative path, made by the server
            $table->string('photo_thumbnail_path')->nullable()->after('photo_path');
            $table->unsignedSmallInteger('photo_width')->nullable()->after('photo_thumbnail_path');
            $table->unsignedSmallInteger('photo_height')->nullable()->after('photo_width');
        });

        // A photo is all four columns or none, so a page never shows half a photo.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE agricultural_products ADD CONSTRAINT agricultural_products_photo_check CHECK (
                (photo_path IS NULL AND photo_thumbnail_path IS NULL AND photo_width IS NULL AND photo_height IS NULL)
                OR (photo_path IS NOT NULL AND photo_thumbnail_path IS NOT NULL AND photo_width IS NOT NULL AND photo_height IS NOT NULL))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE agricultural_products DROP CHECK agricultural_products_photo_check');
        }

        Schema::table('agricultural_products', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_thumbnail_path', 'photo_width', 'photo_height']);
        });
    }
};
