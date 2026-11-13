<?php

namespace Database\Seeders;

use App\Models\CropListing;
use App\Models\Role;
use App\Models\User;
use App\Services\CropListingService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Demo data for F1 (crop listings), for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=CropListingDemoSeeder
 *
 * Creates demo farmers and a demo buyer (password: "password") and 11 crop listings with real crop photos
 * from Unsplash (free Unsplash License: https://unsplash.com/license). The photos are downloaded once, when
 * this runs, so it needs an internet connection. Each listing is saved through CropListingService, so the
 * photos are re-encoded and stored privately exactly like a farmer's own upload.
 *
 * Karim and Salma are the same demo accounts as FarmJobDemoSeeder (F20), so either seeder can run first.
 * Safe to run twice: existing accounts are never changed, and listings are added only if no demo farmer has any.
 */
class CropListingDemoSeeder extends Seeder
{
    /**
     * [Unsplash page id (credit: unsplash.com/photos/{id}), image id on Unsplash's CDN (images.unsplash.com/{id})].
     * Checked by hand: each shows the crop named, and no faces. All are free photos (not Unsplash+).
     */
    private const PHOTOS = [
        'potato_pile' => ['h3AkzboxK4Q', 'photo-1590165482129-1b8b27698780'],
        'potato_field' => ['484GsKrL5r8', 'photo-1573196444577-af471298e034'],
        'potato_dug' => ['gV6p1oxDMog', 'photo-1663168883715-8e441675d216'],
        'paddy_drying' => ['_0k7kPHu6hM', 'photo-1743300290267-3ba7a8ce7095'],
        'paddy_stalks' => ['1V64AO7F0Y0', 'photo-1759646850616-8bc3e6567ea5'],
        'paddy_field' => ['VsNWi5gNIbE', 'photo-1561504935-4e7d4516a2d1'],
        'onion_pile' => ['dmlyEy_VK30', 'photo-1642582037312-9b9639be89e6'],
        'onion_dark' => ['PNZIcBkVxHY', 'photo-1681758442447-f36ed14c0a8a'],
        'onion_market' => ['U4YdDVliDlA', 'photo-1467019972079-a273e1bc9173'],
        'chilli_pile' => ['uW1vWdN-vmI', 'photo-1504394448285-7adeab975d7d'],
        'chilli_market' => ['4ijhsBXLY0c', 'photo-1509085051020-b0707cdec216'],
        'tomato_crate' => ['VV-KRDpSuek', 'photo-1753172433718-d0c2a99443d8'],
        'mango_pile' => ['7DrqdqKTroY', 'photo-1757281096712-d0c9fb17d608'],
        'pumpkin_pile' => ['EumiYHAaKtg', 'photo-1539526450641-cfe3adaa1469'],
        'cauliflower_market' => ['hnEp8wnCj4g', 'photo-1692956706779-576c151ec712'],
        'cauliflower_field' => ['-z79RWojNPI', 'photo-1568584952634-e9bb8a163e28'],
        'cauliflower_leaves' => ['D8ZrftlvNDE', 'photo-1568584711075-3d021a7c3ca3'],
    ];

    public function run(CropListingService $listings): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('CropListingDemoSeeder is for development only. Nothing was added.');

            return;
        }

        $karim = $this->demoUser('01700000001', 'Karim Mia (demo farmer)', [Role::FARMER]);
        $salma = $this->demoUser('01700000002', 'Salma Khatun (demo farmer and worker)', [Role::FARMER, Role::WORKER]);
        $hamid = $this->demoUser('01700000004', 'Abdul Hamid (demo farmer)', [Role::FARMER]);
        $nasrin = $this->demoUser('01700000005', 'Nasrin Akter (demo farmer)', [Role::FARMER, Role::BUYER]);
        $this->demoUser('01700000006', 'Tania Rahman (demo buyer)', [Role::BUYER]);

        $farmers = [$karim, $salma, $hamid, $nasrin];
        if (CropListing::withTrashed()->whereIn('farmer_id', collect($farmers)->pluck('id'))->exists()) {
            $this->command?->info('Demo crop listings already exist. Nothing was added.');

            return;
        }

        $today = Carbon::parse(CropListing::today());

        // [farmer, photos, days since harvest, days since publishing, visible?, fields, delivery]
        $demo = [
            [$karim, ['potato_pile', 'potato_field'], 6, 5, true, [
                'crop_name' => 'Potato — Diamant', 'quantity' => '2500', 'unit_code' => 'kg', 'price_per_unit' => '22',
                'quality_description' => "Medium to large size, sorted by hand. No rot or green skin.\nStored in a dry, shaded room since harvest.",
                'public_area' => 'Sirajdikhan, Munshiganj', 'delivery_notes' => 'Truck loading help available at the farm road.',
            ], ['pickup', 'buyer_arranged']],
            [$karim, ['paddy_drying', 'paddy_stalks'], 10, 8, true, [
                'crop_name' => 'Aman paddy — BR-11', 'quantity' => '3', 'unit_code' => 'tonne', 'price_per_unit' => '30000',
                'quality_description' => 'Sun-dried for three days, about 14% moisture. Cleaned with a fan, few empty grains.',
                'public_area' => 'Dhamrai, Dhaka', 'delivery_notes' => null,
            ], ['pickup', 'farmer_arranged']],
            [$karim, ['cauliflower_market', 'cauliflower_field'], 1, 1, true, [
                'crop_name' => 'Cauliflower (early winter)', 'quantity' => '400', 'unit_code' => 'piece', 'price_per_unit' => '35',
                'quality_description' => 'White, tight heads of about 700–900 g each. Cut yesterday morning, kept in shade.',
                'public_area' => 'Savar, Dhaka', 'delivery_notes' => 'Best collected within two days of the order.',
            ], ['pickup', 'farmer_arranged', 'third_party']],
            [$salma, ['chilli_pile', 'chilli_market'], 2, 2, true, [
                'crop_name' => 'Green chilli — Bogura local', 'quantity' => '300', 'unit_code' => 'kg', 'price_per_unit' => '80',
                'quality_description' => 'Fresh, firm and fully green. Sorted, broken ones removed.',
                'public_area' => 'Shibganj, Bogura', 'delivery_notes' => 'Packed in 20 kg net bags.',
            ], ['pickup', 'buyer_arranged', 'third_party']],
            [$salma, ['pumpkin_pile'], 12, 11, true, [
                'crop_name' => 'Sweet pumpkin (mishti kumra)', 'quantity' => '150', 'unit_code' => 'piece', 'price_per_unit' => '60',
                'quality_description' => 'Ripe, hard skin, 3–5 kg each. Keeps for several weeks in a dry place.',
                'public_area' => 'Shibganj, Bogura', 'delivery_notes' => null,
            ], ['pickup', 'farmer_arranged']],
            [$salma, ['potato_dug'], 20, 18, false, [ // hidden from buyers: shows the "Hidden" state in her listings
                'crop_name' => 'Potato — Granola (seed size)', 'quantity' => '600', 'unit_code' => 'kg', 'price_per_unit' => '28',
                'quality_description' => 'Small, even seed-size tubers with some soil on them. Not washed.',
                'public_area' => 'Shibganj, Bogura', 'delivery_notes' => null,
            ], ['pickup']],
            [$hamid, ['mango_pile'], 3, 3, true, [
                'crop_name' => 'Mango — Gourmati (late variety)', 'quantity' => '800', 'unit_code' => 'kg', 'price_per_unit' => '120',
                'quality_description' => 'Naturally ripened, no carbide. Medium size, a few with small skin marks.',
                'public_area' => 'Shibganj, Chapainawabganj', 'delivery_notes' => 'Packed in 20 kg crates. Courier booking possible on request.',
            ], ['buyer_arranged', 'farmer_arranged', 'third_party']],
            [$hamid, ['onion_pile', 'onion_dark', 'onion_market'], 25, 21, true, [
                'crop_name' => 'Red onion — Faridpuri', 'quantity' => '1200', 'unit_code' => 'kg', 'price_per_unit' => '55',
                'quality_description' => 'Dry, cured for three weeks. Medium bulbs, tops and roots cut.',
                'public_area' => 'Sujanagar, Pabna', 'delivery_notes' => '40 kg jute bags.',
            ], ['pickup', 'buyer_arranged']],
            [$nasrin, ['tomato_crate'], 1, 0, true, [
                'crop_name' => 'Tomato — mixed local varieties', 'quantity' => '250', 'unit_code' => 'kg', 'price_per_unit' => '45.50',
                'quality_description' => 'Red and yellow, ripe to half-ripe. Packed in plastic crates to avoid bruising.',
                'public_area' => 'Kaliganj, Jhenaidah', 'delivery_notes' => null,
            ], ['pickup', 'third_party']],
            [$nasrin, ['paddy_field'], 15, 14, true, [
                'crop_name' => 'Paddy — BRRI dhan49', 'quantity' => '5', 'unit_code' => 'tonne', 'price_per_unit' => '29000',
                'quality_description' => 'Harvested by machine, dried and stored in sacks. Grain is long and slender.',
                'public_area' => 'Kaliganj, Jhenaidah', 'delivery_notes' => 'Loading from the village road only.',
            ], ['pickup', 'buyer_arranged']],
            [$nasrin, ['cauliflower_leaves'], 2, 1, true, [
                'crop_name' => 'Cauliflower with leaves', 'quantity' => '200', 'unit_code' => 'piece', 'price_per_unit' => '30',
                'quality_description' => 'Heads still in their leaves, which keeps them fresh longer. Mixed sizes.',
                'public_area' => 'Jhikargacha, Jashore', 'delivery_notes' => null,
            ], ['pickup']],
        ];

        $downloaded = $this->downloadPhotos(collect($demo)->pluck(1)->flatten()->unique()->all());
        $made = 0;

        foreach ($demo as [$farmer, $photoKeys, $harvestedDaysAgo, $publishedDaysAgo, $visible, $fields, $delivery]) {
            $uploads = array_values(array_filter(array_map(fn ($key) => $downloaded[$key] ?? null, $photoKeys)));
            if ($uploads === []) {
                $this->command?->warn("Skipped \"{$fields['crop_name']}\": its photo could not be downloaded.");

                continue;
            }

            $fields['harvest_date'] = $today->copy()->subDays($harvestedDaysAgo)->toDateString();
            [$listing] = $listings->create($farmer, $fields, $delivery, (string) Str::uuid(), $uploads);

            if (! $visible) {
                $listing = $listings->setVisibility($listing, $listing->version, publish: false);
            }

            // Spread the listings over the last weeks, so the marketplace order and "Updated" dates look realistic.
            $when = now()->subDays($publishedDaysAgo)->subMinutes($made * 7);
            CropListing::whereKey($listing->id)->toBase()->update(['published_at' => $when, 'created_at' => $when, 'updated_at' => $when]);
            $made++;
        }

        foreach ($downloaded as $file) {
            @unlink($file->getRealPath());
        }

        $this->command?->info("Added {$made} demo crop listings with Unsplash photos.");
        $this->command?->info('Farmers: 01700000001 (Karim), 01700000002 (Salma), 01700000004 (Hamid), 01700000005 (Nasrin). Buyer: 01700000006 (Tania). Password: "password".');
    }

    /**
     * Download each Unsplash photo once (1600 px wide JPEG) into a temporary file.
     *
     * @param  list<string>  $keys
     * @return array<string, UploadedFile>
     */
    private function downloadPhotos(array $keys): array
    {
        $files = [];
        $http = Http::timeout(60)
            ->withUserAgent('KrishiBondhu-demo-seeder (CSE470 student project, development only)')
            ->withOptions(['verify' => $this->certificateBundle()]);
        foreach ($keys as $key) {
            [$id, $cdnId] = self::PHOTOS[$key];
            try {
                // Unsplash's image CDN, the address Unsplash intends apps to load photos from.
                $response = $http->get("https://images.unsplash.com/{$cdnId}", ['w' => 1600, 'q' => 85, 'fm' => 'jpg']);
                if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/jpeg')) {
                    $this->command?->warn("Unsplash photo {$id} could not be downloaded (HTTP {$response->status()}).");

                    continue;
                }
                $path = tempnam(sys_get_temp_dir(), 'kb-demo-');
                file_put_contents($path, $response->body());
                // test: true lets a file that was not sent by a browser pass Laravel's "is this an upload" check.
                $files[$key] = new UploadedFile($path, $key.'.jpg', 'image/jpeg', null, true);
                $this->command?->line("  downloaded {$key} (unsplash.com/photos/{$id})");
            } catch (Throwable $e) {
                $this->command?->warn("Unsplash photo {$id} could not be downloaded: {$e->getMessage()}");
            }
        }

        return $files;
    }

    /**
     * HTTPS certificates are always checked. A PHP installed with WinGet often has no certificate list
     * configured (curl.cainfo is empty), so use the one PHP is configured with, or else the list that
     * Git for Windows installs. With neither, PHP's default is used (true).
     */
    private function certificateBundle(): string|bool
    {
        $candidates = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            'C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt',
            'C:/Program Files/Git/usr/ssl/certs/ca-bundle.crt',
        ];
        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return true;
    }

    /** Find the demo account by phone, or create it. An existing account is left exactly as it is. */
    private function demoUser(string $phone, string $name, array $roles): User
    {
        $user = User::firstOrCreate(['phone' => $phone], ['name' => $name, 'password' => 'password', 'locale' => 'en']);

        if ($user->wasRecentlyCreated) {
            $user->roles()->attach(Role::whereIn('slug', $roles)->pluck('id'));
        }

        return $user;
    }
}
