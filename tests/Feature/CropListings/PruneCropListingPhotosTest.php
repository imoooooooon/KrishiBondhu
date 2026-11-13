<?php

namespace Tests\Feature\CropListings;

use App\Models\CropListing;
use App\Services\CropListingPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F1 spec §14 step 10: the cleanup command deletes only old, unused files inside the F1 folder. */
class PruneCropListingPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_old_unreferenced_f1_files_are_deleted_and_only_with_apply(): void
    {
        $disk = Storage::fake(CropListingPhotoService::DISK);
        $dir = CropListingPhotoService::DIRECTORY;

        $active = CropListing::factory()->withPhotos()->create()->photos()->first();
        $removed = CropListing::factory()->withPhotos()->create();
        $removedPhoto = $removed->photos()->first();
        $removed->delete(); // a removed listing still references its files

        $disk->put("{$dir}/old-orphan.jpg", 'x');
        $disk->put("{$dir}/new-orphan.jpg", 'x');
        $disk->put('other-feature/old.jpg', 'x');
        $old = now()->subHours(48)->getTimestamp();
        foreach (["{$dir}/old-orphan.jpg", 'other-feature/old.jpg', $active->display_path, $removedPhoto->display_path] as $path) {
            touch($disk->path($path), $old);
        }

        $this->artisan('crop-listings:prune-photos')->expectsOutputToContain("Would delete {$dir}/old-orphan.jpg")->assertSuccessful();
        $disk->assertExists("{$dir}/old-orphan.jpg"); // dry run by default

        $this->artisan('crop-listings:prune-photos', ['--apply' => true])->assertSuccessful();

        $disk->assertMissing("{$dir}/old-orphan.jpg");
        $disk->assertExists(["{$dir}/new-orphan.jpg", 'other-feature/old.jpg', $active->display_path, $removedPhoto->display_path]);
    }
}
