<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('subject_name', 120);
            $table->text('problem');
            $table->text('treatment_notes');
            $table->date('treated_on');
            $table->date('follow_up_date')->nullable();
            $table->string('status', 20)->default('ongoing');
            $table->timestamps();

            $table->index(['farmer_id', 'treated_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_records');
    }
};
