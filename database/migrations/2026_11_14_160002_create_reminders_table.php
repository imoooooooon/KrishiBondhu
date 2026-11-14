<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {$table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();$table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();$table->string('title', 160);
            $table->text('notes')->nullable();$table->date('remind_on');
            $table->timestamp('completed_at')->nullable();$table->timestamps();

            $table->index(['user_id', 'completed_at', 'remind_on']);$table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};