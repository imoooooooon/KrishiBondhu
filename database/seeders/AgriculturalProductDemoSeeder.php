<?php

namespace Database\Seeders;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use App\Services\AgriculturalProductService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Demo data for F25 (agricultural product sales), for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=AgriculturalProductDemoSeeder
 *
 * Two demo sellers with products in every category: some accept bulk offers, one is out of stock and one is hidden
 * from the shop. Each product gets one free Unsplash photo (https://unsplash.com/license), downloaded when this runs,
 * so it needs an internet connection; a product whose photo cannot be downloaded is added without one.
 * Product and brand names are generic examples, not real products. Every product goes through
 * AgriculturalProductService::create, the same path as the seller form. Password for every account: "password".
 * Safe to run twice: if the demo products already exist, it only adds a photo to the demo products that have none.
 */
class AgriculturalProductDemoSeeder extends Seeder
{
    use DemoAccounts;

    /**
     * [Unsplash page id (credit: unsplash.com/photos/{id}), image id on Unsplash's CDN (images.unsplash.com/{id})].
     * Checked by hand: each shows the product named (or something close to it) and no faces. All are free photos (not Unsplash+).
     */
    private const PHOTOS = [
        'urea' => ['jvFgC2F4gf8', 'photo-1710223221719-6251cb1b5c5b'],          // white fertilizer prills
        'tsp' => ['5-LBYIucVGM', 'photo-1757670919588-1fe3b3df3dfa'],           // pile of granules
        'seed_packets' => ['Kp7N_4VoICw', 'photo-1752051665095-be37be749e55'],  // seed packets on a shelf
        'sprayer' => ['7veuuatT-jA', 'photo-1749030432965-da86e3abc957'],       // yellow tank sprayer
        'sickle' => ['KDelqmGKNY4', 'photo-1592081398099-a011c4367b7d'],        // sickle on a wooden stump
        'compost' => ['lyMZdag-zgQ', 'photo-1545333212-ffebc7933c12'],          // hands holding dark compost
        'paddy_seed' => ['wqb_dTZCNys', 'photo-1536153635972-1fc2e818f642'],    // paddy grains in a basket
        'oil_bottle' => ['RcLswUjofTE', 'photo-1666694890460-37ec16b0df47'],    // bottle of oil with leaves
        'oil_cake' => ['xJuSFSxf4Qk', 'photo-1770123366817-743776f66d6a'],      // spoon of brown meal
        'drip_pipe' => ['3f7AWW0YWAA', 'photo-1748432171507-c1d62fe2e859'],     // drip line along young plants
    ];

    public function run(AgriculturalProductService $products): void
    {
        if ($this->refuseInProduction()) {
            return;
        }

        $rahim = $this->demoUser('01700000012', 'Rahim Krishi Bitan (demo seller)', [Role::SELLER]);
        $mitu = $this->demoUser('01700000013', 'Mitu Agro Store (demo seller)', [Role::SELLER]);

        $savar = 'Savar Bazar, Dhaka';
        $bogura = 'Shibganj, Bogura';

        // [seller, listed (days ago), visible?, photo key, fields, delivery]
        $demo = [
            [$rahim, 9, true, 'urea', ['name' => 'Urea fertilizer', 'brand' => null, 'category' => 'fertilizer', 'unit_code' => 'bag',
                'unit_price' => '1350', 'stock_quantity' => '120', 'bulk_min_quantity' => '20', 'bulk_min_unit_price' => '1280',
                'description' => '50 kg bag of granular urea. Stored indoors on pallets, bags sealed.',
                'public_area' => $savar, 'delivery_notes' => 'Van delivery inside Savar for orders of 5 bags or more.'], ['pickup', 'seller_arranged']],
            [$rahim, 9, true, 'tsp', ['name' => 'TSP (triple super phosphate)', 'brand' => null, 'category' => 'fertilizer', 'unit_code' => 'kg',
                'unit_price' => '32', 'stock_quantity' => '800', 'bulk_min_quantity' => '200', 'bulk_min_unit_price' => '29',
                'description' => 'Loose TSP sold by the kilogram, weighed at the shop. Bring your own sack or buy one for 20 Taka.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup']],
            [$rahim, 6, true, 'seed_packets', ['name' => 'Hybrid tomato seed', 'brand' => 'Sabuj Seeds (demo)', 'category' => 'seed', 'unit_code' => 'packet',
                'unit_price' => '180', 'stock_quantity' => '45', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => '10 g packet, for the winter season. Packed this year; the expiry date is printed on the packet.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup', 'third_party']],
            [$rahim, 4, true, 'sprayer', ['name' => 'Knapsack sprayer, 16 litre', 'brand' => null, 'category' => 'tool', 'unit_code' => 'piece',
                'unit_price' => '1850', 'stock_quantity' => '0', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => 'Hand-pump sprayer with an adjustable nozzle and padded straps. More stock expected next week.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup']], // out of stock: shows that state in the shop
            [$rahim, 2, false, 'sickle', ['name' => 'Sickle (kaste), large', 'brand' => null, 'category' => 'tool', 'unit_code' => 'piece',
                'unit_price' => '250', 'stock_quantity' => '30', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => 'Local blacksmith-made sickle with a wooden handle, sharpened before sale.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup']], // hidden from buyers
            [$mitu, 8, true, 'compost', ['name' => 'Organic compost (vermicompost)', 'brand' => null, 'category' => 'fertilizer', 'unit_code' => 'bag',
                'unit_price' => '450', 'stock_quantity' => '60', 'bulk_min_quantity' => '10', 'bulk_min_unit_price' => '400',
                'description' => '25 kg bag of earthworm compost made on our own farm. Dry, sieved and free of plastic.',
                'public_area' => $bogura, 'delivery_notes' => 'Can be sent by truck to nearby upazilas; the buyer pays the fare.'], ['pickup', 'buyer_arranged', 'seller_arranged']],
            [$mitu, 7, true, 'paddy_seed', ['name' => 'Boro rice seed — BRRI dhan28', 'brand' => null, 'category' => 'seed', 'unit_code' => 'kg',
                'unit_price' => '70', 'stock_quantity' => '350', 'bulk_min_quantity' => '50', 'bulk_min_unit_price' => '64',
                'description' => 'Cleaned and dried seed paddy from last season, tested for germination at the shop.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'buyer_arranged']],
            [$mitu, 5, true, 'oil_bottle', ['name' => 'Neem oil spray', 'brand' => 'Prakriti (demo)', 'category' => 'pesticide', 'unit_code' => 'bottle',
                'unit_price' => '320', 'stock_quantity' => '25', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => '500 ml bottle. Plant-based spray for soft insects on vegetables. Read the label before use.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'third_party']],
            [$mitu, 3, true, 'oil_cake', ['name' => 'Mustard oil cake (khoil)', 'brand' => null, 'category' => 'other', 'unit_code' => 'kg',
                'unit_price' => '55', 'stock_quantity' => '400', 'bulk_min_quantity' => '100', 'bulk_min_unit_price' => '50',
                'description' => 'Used as organic manure and as cattle feed. Fresh from the local oil mill.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'buyer_arranged']],
            [$mitu, 1, true, 'drip_pipe', ['name' => 'Drip irrigation pipe, 100 m roll', 'brand' => null, 'category' => 'tool', 'unit_code' => 'piece',
                'unit_price' => '2400', 'stock_quantity' => '8', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => '16 mm drip pipe with holes every 30 cm. Connectors sold separately.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'third_party']],
        ];

        $sellerIds = [$rahim->id, $mitu->id];
        if (AgriculturalProduct::withTrashed()->whereIn('seller_id', $sellerIds)->exists()) {
            $this->addMissingPhotos($products, $sellerIds, $demo);

            return;
        }

        $downloaded = $this->downloadPhotos(collect($demo)->pluck(3)->unique()->all());

        foreach ($demo as $i => [$seller, $listedDaysAgo, $visible, $photoKey, $fields, $delivery]) {
            [$product] = $products->create($seller, $fields, $delivery, (string) Str::uuid(), $downloaded[$photoKey] ?? null);

            if (! $visible) {
                $product = $products->setVisibility($product, $product->version, publish: false);
            }

            // Spread the products over the last days, so the shop order and dates look realistic.
            $when = now()->subDays($listedDaysAgo)->subMinutes($i * 9);
            AgriculturalProduct::whereKey($product->id)->toBase()
                ->update(['created_at' => $when, 'updated_at' => $when] + ($visible ? ['published_at' => $when] : []));
        }

        $this->deleteDownloads($downloaded);

        $this->command?->info('Added '.count($demo).' demo agricultural products ('.count($downloaded).' with a photo).');
        $this->command?->info('Sellers: 01700000012 (Rahim, Savar), 01700000013 (Mitu, Bogura). Password: "password".');
    }

    /**
     * For demo data made before the products had photos: give each demo product without a photo its photo, through
     * AgriculturalProductService::update (the same path as a seller adding a photo). Nothing else about the product changes.
     *
     * @param  list<int>  $sellerIds
     * @param  list<array>  $demo
     */
    private function addMissingPhotos(AgriculturalProductService $products, array $sellerIds, array $demo): void
    {
        $keyByName = collect($demo)->mapWithKeys(fn ($row) => [$row[4]['name'] => $row[3]]);
        $missing = AgriculturalProduct::whereIn('seller_id', $sellerIds)->whereNull('photo_path')->get()
            ->filter(fn ($product) => $keyByName->has($product->name));

        if ($missing->isEmpty()) {
            $this->command?->info('Demo agricultural products already exist and all have photos. Nothing was added.');

            return;
        }

        $downloaded = $this->downloadPhotos($missing->map(fn ($product) => $keyByName[$product->name])->unique()->values()->all());
        $added = 0;

        foreach ($missing as $product) {
            $photo = $downloaded[$keyByName[$product->name]] ?? null;
            if ($photo === null) {
                continue;
            }

            $updatedAt = $product->updated_at;
            $product = $products->update($product, $product->version, [], $product->deliveryMethods(), $photo);
            AgriculturalProduct::whereKey($product->id)->toBase()->update(['updated_at' => $updatedAt]); // keep the listed dates
            $added++;
        }

        $this->deleteDownloads($downloaded);
        $this->command?->info("Added a photo to {$added} existing demo agricultural products.");
    }

    /**
     * Download each Unsplash photo once (1600 px wide JPEG) into a temporary file. A failed download is skipped.
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
                $response = $http->get("https://images.unsplash.com/{$cdnId}", ['w' => 1600, 'q' => 85, 'fm' => 'jpg']);
                if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/jpeg')) {
                    $this->command?->warn("Unsplash photo {$id} could not be downloaded (HTTP {$response->status()}).");

                    continue;
                }
                $path = tempnam(sys_get_temp_dir(), 'kb-demo-');
                file_put_contents($path, $response->body());
                // test: true lets a file that was not sent by a browser pass Laravel's "is this an upload" check.
                $files[$key] = new UploadedFile($path, $key.'.jpg', 'image/jpeg', null, true);
            } catch (Throwable $e) {
                $this->command?->warn("Unsplash photo {$id} could not be downloaded: {$e->getMessage()}");
            }
        }

        return $files;
    }

    /** Same certificate lookup as CropListingDemoSeeder: a WinGet PHP often has no certificate list configured. */
    private function certificateBundle(): string|bool
    {
        foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile'),
            'C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt', 'C:/Program Files/Git/usr/ssl/certs/ca-bundle.crt'] as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return true;
    }

    /** @param  array<string, UploadedFile>  $files */
    private function deleteDownloads(array $files): void
    {
        foreach ($files as $file) {
            @unlink($file->getRealPath());
        }
    }
}
