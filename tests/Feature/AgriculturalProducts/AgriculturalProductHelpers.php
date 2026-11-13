<?php

namespace Tests\Feature\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use App\Models\CropListing;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/** Shared test data for the F25 tests. */
trait AgriculturalProductHelpers
{
    protected function seller(): User
    {
        return User::factory()->withRoles(Role::SELLER)->create();
    }

    protected function userWith(string ...$roles): User
    {
        return User::factory()->withRoles(...$roles)->create();
    }

    /** @return array<string, mixed> a complete, valid "Add product" form without bulk offers */
    protected function validForm(array $overrides = []): array
    {
        return array_merge([
            'submission_token' => (string) Str::uuid(),
            'name' => 'Urea fertilizer, 50 kg bag',
            'brand' => 'Jamuna',
            'category' => 'fertilizer',
            'description' => 'Granular urea, sealed 50 kg bag. Store in a dry place.',
            'public_area' => 'Shibganj, Bogura',
            'delivery_options' => ['pickup', 'seller_arranged'],
            'delivery_notes' => '',
            'unit_code' => 'bag',
            'unit_price' => '1350.50',
            'stock_quantity' => '40',
        ], $overrides);
    }

    /** @return array<string, mixed> the same form with bulk offers from 10 bags at no less than ৳ 1,250 */
    protected function bulkForm(array $overrides = []): array
    {
        return $this->validForm(array_merge([
            'accepts_bulk_offers' => '1',
            'bulk_min_quantity' => '10',
            'bulk_min_unit_price' => '1250',
        ], $overrides));
    }

    /** @return array<string, mixed> a valid edit form for $product (operation=save, current version) */
    protected function editForm(AgriculturalProduct $product, array $overrides = []): array
    {
        $trim = fn ($value) => $value === null ? '' : CropListing::trimDecimal((string) $value);

        return array_merge([
            'operation' => 'save',
            'version' => $product->version,
            'name' => $product->name,
            'brand' => (string) $product->brand,
            'category' => $product->category,
            'description' => $product->description,
            'public_area' => (string) $product->public_area,
            'delivery_options' => $product->deliveryOptions()->pluck('method')->all(),
            'delivery_notes' => (string) $product->delivery_notes,
            'unit_code' => $product->unit_code,
            'unit_price' => $trim($product->unit_price),
            'stock_quantity' => $trim($product->stock_quantity),
        ], $product->acceptsBulkOffers() ? [
            'accepts_bulk_offers' => '1',
            'bulk_min_quantity' => $trim($product->bulk_min_quantity),
            'bulk_min_unit_price' => $trim($product->bulk_min_unit_price),
        ] : [], $overrides);
    }

    protected function productFor(User $seller): AgriculturalProduct
    {
        return AgriculturalProduct::factory()->for($seller, 'seller')->create();
    }
}
