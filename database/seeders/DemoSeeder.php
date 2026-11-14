<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * All demo data in one command, for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=DemoSeeder
 *
 * Runs every feature's demo seeder (F1, F11, F15, F18, F19, F20, F25) and the F6 cattle demo command.
 * Each part is safe to run again: it skips itself when its demo data already exists.
 * F1 and F6 download photos from Unsplash on their first run, so they need an internet connection.
 * Accounts and logins: docs/demo-assets.md. Password for every demo account: "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoSeeder is for development only. Nothing was added.');

            return;
        }

        $this->call([
            FarmJobDemoSeeder::class,             // F20
            CropListingDemoSeeder::class,         // F1
            MarketPriceDemoSeeder::class,         // F11
            SoilGuidanceDemoSeeder::class,        // F15
            VeterinaryDemoSeeder::class,          // F18
            TreatmentDemoSeeder::class,           // F19
            AgriculturalProductDemoSeeder::class, // F25
        ]);

        // F6 has its own command, which also keeps a manifest so its cleanup removes only what it created.
        if (app()->environment('local')) {
            $this->command?->info('F6 cattle: php artisan kb:demo-cattle seed');
            Artisan::call('kb:demo-cattle', ['action' => 'seed'], $this->command?->getOutput());
        } else {
            $this->command?->warn('F6 cattle demo skipped: kb:demo-cattle runs only when APP_ENV is local.');
        }
    }
}
