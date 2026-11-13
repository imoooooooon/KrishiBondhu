<?php

namespace App\Services;

use App\Exceptions\CropListingConflictException;
use App\Exceptions\UnusableCropPhotoException;
use App\Models\CropListing;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Every write to a crop listing (F1 spec §13). Controllers call this; it keeps the database rows and the
 * photo files consistent with each other.
 *
 * The pattern used in every method:
 *  1. Do slow file work (decoding photos) BEFORE the database transaction, so rows are locked only briefly.
 *  2. In the transaction: lock the listing row (SELECT ... FOR UPDATE), compare the version the farmer's
 *     form was opened with, then change the rows and add 1 to the version.
 *  3. If anything fails, the transaction rolls back and the NEW files of this attempt are deleted.
 *  4. Only after a successful commit are OLD files deleted (a crash before that leaves the old photo intact).
 */
class CropListingService
{
    public function __construct(private readonly CropListingPhotoService $photos) {}

    /**
     * Publish a new listing. Returns the listing and whether it was created by THIS request
     * (false = the same form was already submitted, e.g. a double tap or a retry on a bad connection).
     *
     * @param  array<string, mixed>  $fields  validated CropListing fields
     * @param  list<string>  $deliveryMethods
     * @param  list<UploadedFile>  $uploads
     * @return array{0: CropListing, 1: bool}
     */
    public function create(User $farmer, array $fields, array $deliveryMethods, string $submissionToken, array $uploads): array
    {
        // Same farmer + same form token = the same submission. Includes removed listings, so a removed
        // listing is never brought back by re-sending an old form.
        $existing = $this->findBySubmission($farmer, $submissionToken);
        if ($existing) {
            return [$existing, false];
        }

        $stored = $this->storeUploads($uploads);

        try {
            $listing = DB::transaction(function () use ($farmer, $fields, $deliveryMethods, $submissionToken, $stored) {
                $listing = new CropListing($fields);
                $listing->farmer_id = $farmer->id;
                $listing->status = CropListing::STATUS_PUBLISHED;
                $listing->published_at = now();
                $listing->version = 1;
                $listing->submission_token = $submissionToken;
                $listing->save();

                foreach ($stored as $i => $photo) {
                    $listing->photos()->create($photo + ['position' => $i + 1]);
                }
                $listing->deliveryOptions()->createMany(array_map(fn ($method) => ['method' => $method], $deliveryMethods));

                return $listing;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Two identical submissions raced past the check above; the database let only one win.
            $this->photos->delete($this->pathsOf($stored));
            $winner = $this->findBySubmission($farmer, $submissionToken);
            if ($winner) {
                return [$winner, false];
            }

            throw $e; // a different unique rule failed: a real error, not a duplicate
        } catch (Throwable $e) {
            $this->photos->delete($this->pathsOf($stored));

            throw $e;
        }

        return [$listing, true];
    }

    /**
     * Save edited content. The status (published or not) is kept.
     *
     * @param  array<string, mixed>  $fields
     * @param  list<string>  $deliveryMethods
     * @param  list<int>  $removePhotoIds  photos the farmer ticked "Remove this photo"
     * @param  list<UploadedFile>  $uploads  new photos, added after the kept ones
     * @param  array<string, mixed>  $submittedText  shown back to the farmer if their form was out of date
     */
    public function update(CropListing $listing, int $version, array $fields, array $deliveryMethods, array $removePhotoIds, array $uploads, array $submittedText = []): CropListing
    {
        $stored = $this->storeUploads($uploads);

        try {
            [$listing, $oldPaths] = DB::transaction(function () use ($listing, $version, $fields, $deliveryMethods, $removePhotoIds, $stored, $submittedText) {
                $locked = $this->lockCurrent($listing, $version, $submittedText);
                $current = $locked->photos()->get();

                // Every photo to remove must belong to THIS listing (a forged id from another listing is refused).
                $unknown = array_diff($removePhotoIds, $current->pluck('id')->all());
                if ($unknown !== []) {
                    throw ValidationException::withMessages(['remove_photo_ids' => __('One of the photos to remove does not belong to this listing.')]);
                }

                $finalCount = $current->count() - count($removePhotoIds) + count($stored);
                if ($finalCount < CropListing::MIN_PHOTOS) {
                    throw ValidationException::withMessages(['photos' => __('Keep at least one photo, or add a new one.')]);
                }
                if ($finalCount > CropListing::MAX_PHOTOS) {
                    throw ValidationException::withMessages(['photos' => __('A listing can have at most :max photos. Remove some before adding more.', ['max' => CropListing::MAX_PHOTOS])]);
                }

                $locked->fill($fields);
                $locked->version = $locked->version + 1;
                $locked->save();

                $removed = $current->whereIn('id', $removePhotoIds);
                $oldPaths = $removed->flatMap(fn ($photo) => [$photo->display_path, $photo->thumbnail_path])->values()->all();
                $locked->photos()->whereKey($removed->pluck('id'))->delete();

                // New photos go after the highest position ever used, so positions never collide.
                $next = (int) $current->max('position') + 1;
                foreach ($stored as $i => $photo) {
                    $locked->photos()->create($photo + ['position' => $next + $i]);
                }

                $locked->deliveryOptions()->whereNotIn('method', $deliveryMethods)->delete();
                $missing = array_diff($deliveryMethods, $locked->deliveryOptions()->pluck('method')->all());
                $locked->deliveryOptions()->createMany(array_map(fn ($method) => ['method' => $method], array_values($missing)));

                return [$locked, $oldPaths];
            });
        } catch (Throwable $e) {
            $this->photos->delete($this->pathsOf($stored));

            throw $e;
        }

        $this->photos->delete($oldPaths); // after commit: a failure here only leaves a private orphan file

        return $listing;
    }

    /**
     * Unpublish (hide from buyers) or publish again. Publishing again re-checks the saved listing,
     * because invalid data must never become public.
     */
    public function setVisibility(CropListing $listing, int $version, bool $publish): CropListing
    {
        return DB::transaction(function () use ($listing, $version, $publish) {
            $locked = $this->lockCurrent($listing, $version);

            if ($publish) {
                $this->assertPublishable($locked);
                $locked->status = CropListing::STATUS_PUBLISHED;
                $locked->published_at ??= now(); // first publication time is kept: republishing does not jump the queue
            } else {
                $locked->status = CropListing::STATUS_UNPUBLISHED;
            }
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked;
        });
    }

    /** Remove (soft delete). The rows and private files are kept; the listing disappears everywhere in the app. */
    public function remove(CropListing $listing, int $version): void
    {
        DB::transaction(function () use ($listing, $version) {
            $locked = $this->lockCurrent($listing, $version);
            $locked->version = $locked->version + 1;
            $locked->save();
            $locked->delete();
        });
    }

    /**
     * Lock the listing row until the transaction ends, and make sure the farmer's form is not out of date.
     * A listing removed meanwhile is "not found" (404); a changed one is a conflict (409).
     */
    private function lockCurrent(CropListing $listing, int $version, array $submittedText = []): CropListing
    {
        $locked = CropListing::query()->whereKey($listing->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->version !== $version) {
            throw new CropListingConflictException($locked, $submittedText);
        }

        return $locked;
    }

    /** Before publishing again: the stored listing must still be complete, including real photo files. */
    private function assertPublishable(CropListing $listing): void
    {
        $problems = [];

        $photos = $listing->photos()->get();
        $usable = $photos->filter(fn ($photo) => $this->photos->exists($photo->display_path) && $this->photos->exists($photo->thumbnail_path));
        if ($usable->isEmpty()) {
            $problems[] = __('Add at least one photo before publishing. Edit the listing to add a photo.');
        }
        if ($listing->deliveryOptions()->doesntExist()) {
            $problems[] = __('Choose at least one delivery option before publishing.');
        }
        if ($listing->harvest_date->toDateString() > CropListing::today()) {
            $problems[] = __('The harvest date cannot be in the future.');
        }

        if ($problems !== []) {
            throw ValidationException::withMessages(['operation' => $problems]);
        }
    }

    private function findBySubmission(User $farmer, string $submissionToken): ?CropListing
    {
        return CropListing::withTrashed()
            ->where('farmer_id', $farmer->id)
            ->where('submission_token', $submissionToken)
            ->first();
    }

    /**
     * Process the uploads one at a time. If photo N is unusable, the files already written for this
     * request are deleted and the farmer sees an error on that photo.
     *
     * @param  list<UploadedFile>  $uploads
     * @return list<array<string, mixed>>
     */
    private function storeUploads(array $uploads): array
    {
        $stored = [];
        foreach (array_values($uploads) as $i => $file) {
            try {
                $stored[] = $this->photos->store($file);
            } catch (UnusableCropPhotoException $e) {
                $this->photos->delete($this->pathsOf($stored));

                throw ValidationException::withMessages(['photos.'.$i => $e->userMessage()]);
            } catch (Throwable $e) {
                $this->photos->delete($this->pathsOf($stored));

                throw $e;
            }
        }

        return $stored;
    }

    /**
     * @param  list<array<string, mixed>>  $stored
     * @return list<string>
     */
    private function pathsOf(array $stored): array
    {
        $paths = [];
        foreach ($stored as $photo) {
            $paths[] = $photo['display_path'];
            $paths[] = $photo['thumbnail_path'];
        }

        return $paths;
    }
}
