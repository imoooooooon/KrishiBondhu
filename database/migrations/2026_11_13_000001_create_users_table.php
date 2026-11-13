<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Accounts. One person = one account (SRS §2.3); roles are in role_user.
| People sign in with a Bangladeshi mobile number (01XXXXXXXXX). Email is optional.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 11)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->string('locale', 5)->default('en'); // UI language preference (F24): en | bn
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
