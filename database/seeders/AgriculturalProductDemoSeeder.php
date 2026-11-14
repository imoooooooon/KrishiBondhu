<?php

namespace Database\Seeders;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use App\Services\AgriculturalProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo data for F25 (agricultural product sales), for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=AgriculturalProductDemoSeeder
 *
 * Two demo sellers with products in every category: some accept bulk offers, one is out of stock and one is hidden
 * from the shop. No photos (the seeder works offline), so the shop shows its "no photo" state.
 * Product and brand names are generic examples, not real products. Every product goes through
 * AgriculturalProductService::create, the same path as the seller form. Password for every account: "password".
 * Safe to run twice: nothing is added if a demo seller already has a product.
 */
class AgriculturalProductDemoSeeder extends Seeder
{
    use DemoAccounts;

    public function run(AgriculturalProductService $products): void
    {
        if ($this->refuseInProduction()) {
            return;
        }

        $rahim = $this->demoUser('01700000012', 'Rahim Krishi Bitan (demo seller)', [Role::SELLER]);
        $mitu = $this->demoUser('01700000013', 'Mitu Agro Store (demo seller)', [Role::SELLER]);

        if (AgriculturalProduct::withTrashed()->whereIn('seller_id', [$rahim->id, $mitu->id])->exists()) {
            $this->command?->info('Demo agricultural products already exist. Nothing was added.');

            return;
        }

        $savar = 'Savar Bazar, Dhaka';
        $bogura = 'Shibganj, Bogura';

        // [seller, listed (days ago), visible?, fields, delivery]
        $demo = [
            [$rahim, 9, true, ['name' => 'Urea fertilizer', 'brand' => null, 'category' => 'fertilizer', 'unit_code' => 'bag',
                'unit_price' => '1350', 'stock_quantity' => '120', 'bulk_min_quantity' => '20', 'bulk_min_unit_price' => '1280',
                'description' => '50 kg bag of granular urea. Stored indoors on pallets, bags sealed.',
                'public_area' => $savar, 'delivery_notes' => 'Van delivery inside Savar for orders of 5 bags or more.'], ['pickup', 'seller_arranged']],
            [$rahim, 9, true, ['name' => 'TSP (triple super phosphate)', 'brand' => null, 'category' => 'fertilizer', 'unit_code' => 'kg',
                'unit_price' => '32', 'stock_quantity' => '800', 'bulk_min_quantity' => '200', 'bulk_min_unit_price' => '29',
                'description' => 'Loose TSP sold by the kilogram, weighed at the shop. Bring your own sack or buy one for 20 Taka.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup']],
            [$rahim, 6, true, ['name' => 'Hybrid tomato seed', 'brand' => 'Sabuj Seeds (demo)', 'category' => 'seed', 'unit_code' => 'packet',
                'unit_price' => '180', 'stock_quantity' => '45', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => '10 g packet, for the winter season. Packed this year; the expiry date is printed on the packet.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup', 'third_party']],
            [$rahim, 4, true, ['name' => 'Knapsack sprayer, 16 litre', 'brand' => null, 'category' => 'tool', 'unit_code' => 'piece',
                'unit_price' => '1850', 'stock_quantity' => '0', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => 'Hand-pump sprayer with an adjustable nozzle and padded straps. More stock expected next week.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup']], // out of stock: shows that state in the shop
            [$rahim, 2, false, ['name' => 'Sickle (kaste), large', 'brand' => null, 'category' => 'tool', 'unit_code' => 'piece',
                'unit_price' => '250', 'stock_quantity' => '30', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => 'Local blacksmith-made sickle with a wooden handle, sharpened before sale.',
                'public_area' => $savar, 'delivery_notes' => null], ['pickup']], // hidden from buyers
            [$mitu, 8, true, ['name' => 'Organic compost (vermicompost)', 'brand' => null, 'category' => 'fertilizer', 'unit_code' => 'bag',
                'unit_price' => '450', 'stock_quantity' => '60', 'bulk_min_quantity' => '10', 'bulk_min_unit_price' => '400',
                'description' => '25 kg bag of earthworm compost made on our own farm. Dry, sieved and free of plastic.',
                'public_area' => $bogura, 'delivery_notes' => 'Can be sent by truck to nearby upazilas; the buyer pays the fare.'], ['pickup', 'buyer_arranged', 'seller_arranged']],
            [$mitu, 7, true, ['name' => 'Boro rice seed — BRRI dhan28', 'brand' => null, 'category' => 'seed', 'unit_code' => 'kg',
                'unit_price' => '70', 'stock_quantity' => '350', 'bulk_min_quantity' => '50', 'bulk_min_unit_price' => '64',
                'description' => 'Cleaned and dried seed paddy from last season, tested for germination at the shop.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'buyer_arranged']],
            [$mitu, 5, true, ['name' => 'Neem oil spray', 'brand' => 'Prakriti (demo)', 'category' => 'pesticide', 'unit_code' => 'bottle',
                'unit_price' => '320', 'stock_quantity' => '25', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => '500 ml bottle. Plant-based spray for soft insects on vegetables. Read the label before use.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'third_party']],
            [$mitu, 3, true, ['name' => 'Mustard oil cake (khoil)', 'brand' => null, 'category' => 'other', 'unit_code' => 'kg',
                'unit_price' => '55', 'stock_quantity' => '400', 'bulk_min_quantity' => '100', 'bulk_min_unit_price' => '50',
                'description' => 'Used as organic manure and as cattle feed. Fresh from the local oil mill.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'buyer_arranged']],
            [$mitu, 1, true, ['name' => 'Drip irrigation pipe, 100 m roll', 'brand' => null, 'category' => 'tool', 'unit_code' => 'piece',
                'unit_price' => '2400', 'stock_quantity' => '8', 'bulk_min_quantity' => null, 'bulk_min_unit_price' => null,
                'description' => '16 mm drip pipe with holes every 30 cm. Connectors sold separately.',
                'public_area' => $bogura, 'delivery_notes' => null], ['pickup', 'third_party']],
        ];

        foreach ($demo as $i => [$seller, $listedDaysAgo, $visible, $fields, $delivery]) {
            [$product] = $products->create($seller, $fields, $delivery, (string) Str::uuid());

            if (! $visible) {
                $product = $products->setVisibility($product, $product->version, publish: false);
            }

            // Spread the products over the last days, so the shop order and dates look realistic.
            $when = now()->subDays($listedDaysAgo)->subMinutes($i * 9);
            AgriculturalProduct::whereKey($product->id)->toBase()
                ->update(['created_at' => $when, 'updated_at' => $when] + ($visible ? ['published_at' => $when] : []));
        }

        $this->command?->info('Added '.count($demo).' demo agricultural products.');
        $this->command?->info('Sellers: 01700000012 (Rahim, Savar), 01700000013 (Mitu, Bogura). Password: "password".');
    }
}
