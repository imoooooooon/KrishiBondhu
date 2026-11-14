<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('veterinarian_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('district', 60);
            $table->string('upazila', 80);
            $table->string('address', 255);
            $table->boolean('treats_cattle')->default(false);
            $table->boolean('treats_goats')->default(false);
            $table->boolean('treats_poultry')->default(false);
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_listed')->default(false);
            $table->timestamps();

            $table->index(['district', 'is_listed']); // the vet list is filtered by district and only shows listed vets
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veterinarian_profiles');
    }
};
