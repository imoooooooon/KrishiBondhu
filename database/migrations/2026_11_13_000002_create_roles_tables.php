<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| The eight user roles from SRS §2.3, and which users hold them.
| The roles are fixed reference data the app cannot work without, so they are
| inserted here: `php artisan migrate` alone gives a working database.
| Add a role only through a new migration (and update SRS §2.3 and App\Models\Role).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();   // used in code: $user->hasRole('farmer')
            $table->string('name', 80);             // English name; Bangla comes from lang/bn.json
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
        });

        $now = now();
        DB::table('roles')->insert(array_map(fn (array $role) => $role + ['created_at' => $now, 'updated_at' => $now], [
            ['slug' => 'farmer', 'name' => 'Farmer'],
            ['slug' => 'buyer', 'name' => 'Buyer'],
            ['slug' => 'worker', 'name' => 'Agricultural worker'],
            ['slug' => 'insemination-agent', 'name' => 'Insemination agent'],
            ['slug' => 'price-contributor', 'name' => 'Market price contributor'],
            ['slug' => 'veterinarian', 'name' => 'Veterinarian'],
            ['slug' => 'seller', 'name' => 'Agricultural product seller'],
            ['slug' => 'admin', 'name' => 'Administrator'],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
