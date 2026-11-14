<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('veterinarian_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->date('preferred_date');
            $table->string('animal_type', 20);
            $table->text('problem');
            $table->string('farm_address', 255);
            $table->text('vet_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['veterinarian_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_requests');
    }
};
