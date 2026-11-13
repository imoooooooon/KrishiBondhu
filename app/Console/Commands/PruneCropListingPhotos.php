<?php

namespace App\Console\Commands;

use App\Models\CropListingPhoto;
use App\Services\CropListingPhotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Finds photo files that no crop listing uses any more (F1 spec §14, step 10) and, with --apply, deletes them.
 *
 * How orphans happen: a server crash between writing a new photo and saving its row, or a failed delete of an
 * old photo after an edit. They are private, so they are not a leak, only wasted disk space.
 *
 * Safe by design:
 * - dry run by default: it only lists what it would delete;
 * - looks only inside the F1 folder (crop-listing-photos/), never at other features' uploads;
 * - a file is kept if ANY photo row points at it, including photos of removed (soft-deleted) listings;
 * - files younger than --hours (default 24) are kept, because a save may be in progress right now.
 *
 * Run manually: php artisan crop-listings:prune-photos            (shows what it would do)
 *               php artisan crop-listings:prune-photos --apply    (deletes)
 */
class PruneCropListingPhotos extends Command
{
    protected $signature = 'crop-listings:prune-photos {--apply : Delete the files (without it, only list them)} {--hours=24 : Keep files newer than this many hours}';

    protected $description = 'List (or delete with --apply) crop listing photo files that no listing uses';

    public function handle(): int
    {
        $disk = Storage::disk(CropListingPhotoService::DISK);
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours)->getTimestamp();

        $referenced = [];
        CropListingPhoto::query()->select(['id', 'display_path', 'thumbnail_path'])->chunkById(500, function ($photos) use (&$referenced) {
            foreach ($photos as $photo) {
                $referenced[$photo->display_path] = true;
                $referenced[$photo->thumbnail_path] = true;
            }
        });

        $orphans = [];
        foreach ($disk->files(CropListingPhotoService::DIRECTORY) as $path) {
            if (! isset($referenced[$path]) && $disk->lastModified($path) < $cutoff) {
                $orphans[] = $path;
            }
        }

        if ($orphans === []) {
            $this->info('No unused crop listing photo files older than '.$hours.' hours.');

            return self::SUCCESS;
        }

        foreach ($orphans as $path) {
            $this->line(($this->option('apply') ? 'Deleting ' : 'Would delete ').$path);
        }

        if (! $this->option('apply')) {
            $this->warn(count($orphans).' unused file(s) found. Run again with --apply to delete them.');

            return self::SUCCESS;
        }

        $disk->delete($orphans);
        $this->info(count($orphans).' unused file(s) deleted.');

        return self::SUCCESS;
    }
}
