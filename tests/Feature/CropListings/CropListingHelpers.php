<?php

namespace Tests\Feature\CropListings;

use App\Models\CropListing;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Shared test data for the F1 tests. Photos are real images made by GD, so decoding is really tested. */
trait CropListingHelpers
{
    protected function farmer(): User
    {
        return User::factory()->withRoles(Role::FARMER)->create();
    }

    protected function buyer(): User
    {
        return User::factory()->withRoles(Role::BUYER)->create();
    }

    protected function daysFromToday(int $days): string
    {
        return Carbon::parse(CropListing::today())->addDays($days)->toDateString();
    }

    protected function photo(string $name = 'crop.jpg', int $width = 640, int $height = 400): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    /** @return array<string, mixed> a complete, valid "Add crop listing" form */
    protected function validForm(array $overrides = []): array
    {
        return array_merge([
            'submission_token' => (string) Str::uuid(),
            'crop_name' => 'Potato — Diamant',
            'quality_description' => 'Medium size, sorted, no rot.',
            'harvest_date' => $this->daysFromToday(-2),
            'quantity' => '250',
            'unit_code' => 'kg',
            'price_per_unit' => '35.50',
            'public_area' => 'Shibganj, Bogura',
            'delivery_options' => ['pickup', 'farmer_arranged'],
            'delivery_notes' => '',
            'photos' => [$this->photo()],
        ], $overrides);
    }

    /** @return array<string, mixed> a valid edit form for $listing (operation=save, current version) */
    protected function editForm(CropListing $listing, array $overrides = []): array
    {
        return array_merge([
            'operation' => 'save',
            'version' => $listing->version,
            'crop_name' => $listing->crop_name,
            'quality_description' => $listing->quality_description,
            'harvest_date' => $listing->harvest_date->toDateString(),
            'quantity' => (string) $listing->quantity,
            'unit_code' => $listing->unit_code,
            'price_per_unit' => (string) $listing->price_per_unit,
            'public_area' => $listing->public_area,
            'delivery_options' => $listing->deliveryMethods(),
            'delivery_notes' => (string) $listing->delivery_notes,
        ], $overrides);
    }

    /** A farmer's published listing with real photo files on the (faked) disk. */
    protected function listingFor(User $farmer, int $photos = 1): CropListing
    {
        return CropListing::factory()->for($farmer, 'farmer')->withPhotos($photos)->create()->load(['photos', 'deliveryOptions']);
    }
}
