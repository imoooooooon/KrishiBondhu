<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Markets: shared reference data for F11 (prices) and F12 (map and distance).
| Spec: docs/features/F11-market-price-tracking.md.
|
| - Minimal on purpose: F12 adds coordinates in its own migration, and administrators will manage markets
|   later through /admin/markets (IA.md §5.9). Until then, the initial markets below are inserted here,
|   like the roles, so `php artisan migrate` alone gives contributors markets to choose from.
| - name_bn: market names are data (admins will add more), so the Bangla name is a column, not a translation key.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('name_bn', 120);
            $table->string('upazila', 80);
            $table->string('district', 60);
            $table->timestamps();

            $table->unique(['name', 'district']);
            $table->index(['district', 'name']); // the market list is ordered by district, then name
        });

        $now = now();
        DB::table('markets')->insert(array_map(fn (array $market) => $market + ['created_at' => $now, 'updated_at' => $now], [
            ['name' => 'Kawran Bazar', 'name_bn' => 'কারওয়ান বাজার', 'upazila' => 'Tejgaon', 'district' => 'Dhaka'],
            ['name' => 'Shyambazar', 'name_bn' => 'শ্যামবাজার', 'upazila' => 'Sutrapur', 'district' => 'Dhaka'],
            ['name' => 'Jatrabari', 'name_bn' => 'যাত্রাবাড়ী', 'upazila' => 'Jatrabari', 'district' => 'Dhaka'],
            ['name' => 'Khatunganj', 'name_bn' => 'খাতুনগঞ্জ', 'upazila' => 'Kotwali', 'district' => 'Chattogram'],
            ['name' => 'Reazuddin Bazar', 'name_bn' => 'রিয়াজউদ্দিন বাজার', 'upazila' => 'Kotwali', 'district' => 'Chattogram'],
            ['name' => 'Mohasthan Hat', 'name_bn' => 'মহাস্থান হাট', 'upazila' => 'Shibganj', 'district' => 'Bogura'],
            ['name' => 'Baneshwar Hat', 'name_bn' => 'বানেশ্বর হাট', 'upazila' => 'Puthia', 'district' => 'Rajshahi'],
            ['name' => 'Shaheb Bazar', 'name_bn' => 'সাহেব বাজার', 'upazila' => 'Boalia', 'district' => 'Rajshahi'],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
