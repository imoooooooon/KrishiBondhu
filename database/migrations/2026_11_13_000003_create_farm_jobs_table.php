<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Farm job posts (F20). A farmer posts a job; workers search the open ones.
| One job has one work date. Applications and bookings (F21) will point at farm_jobs.id later,
| so jobs are closed, never deleted.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_jobs', function (Blueprint $table) {
            $table->id();
            // The farmer who posted the job. restrictOnDelete: a user with job history cannot be silently removed.
            $table->foreignId('farmer_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->string('work_type', 30);            // one of FarmJob::WORK_TYPES
            $table->string('location');
            $table->date('work_date');
            $table->unsignedSmallInteger('required_workers');
            $table->decimal('proposed_wage', 10, 2);    // taka per worker; decimal, never float, for money
            $table->string('wage_basis', 10);           // daily | task | unit
            $table->string('unit_label', 50)->nullable(); // only for unit wages: bag, maund, bigha ...
            $table->text('meal_details')->nullable();
            $table->text('transport_details')->nullable();
            $table->string('status', 10)->default('open'); // open | closed
            $table->timestamps();

            // Worker search: open jobs from today onwards, ordered by date.
            $table->index(['status', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_jobs');
    }
};
