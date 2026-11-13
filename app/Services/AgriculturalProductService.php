<?php

namespace App\Services;

use App\Exceptions\AgriculturalProductConflictException;
use App\Exceptions\UnusableCropPhotoException;
use App\Models\AgriculturalProduct;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Every write to a farm-supply product (F25). Controllers call this; nothing else changes product rows.
 *
 * The pattern used in every change:
 *  1. Slow file work (decoding the photo) happens BEFORE the database transaction, so the row is locked only briefly.
 *  2. In a transaction, lock the product row (SELECT ... FOR UPDATE) and compare the version the seller's page
 *     was opened with. Different = someone changed it meanwhile (another tab, another phone): refuse with a 409
 *     instead of overwriting newer stock or prices. Then change the row and add 1 to the version.
 *  3. If anything fails, the transaction rolls back and the NEW photo files of this attempt are deleted.
 *  4. Only after a successful commit is an OLD photo deleted (a crash before that leaves the old photo intact).
 *
 * Photos are processed by F1's CropListingPhotoService (used as it is, with F25's own folder).
 *
 * Contract for F5 (orders) and F3 (offers), which will also change stock: lock the row the same way and
 * add 1 to the version, so this service's stale-tab check also sees their changes.
 */
class AgriculturalProductService
{
    public function __construct(private readonly CropListingPhotoService $photos) {}

    /**
     * Add and publish a new product. Returns the product and whether it was created by THIS request
     * (false = the same form was already submitted, e.g. a double tap or a retry on a bad connection).
     *
     * @param  array<string, mixed>  $fields  validated AgriculturalProduct fields
     * @param  list<string>  $deliveryMethods
     * @return array{0: AgriculturalProduct, 1: bool}
     */
    public function create(User $seller, array $fields, array $deliveryMethods, string $submissionToken, ?UploadedFile $photo = null): array
    {
        // Same seller + same form token = the same submission. Includes removed products, so a removed
        // product is never brought back by re-sending an old form.
        $existing = $this->findBySubmission($seller, $submissionToken);
        if ($existing) {
            return [$existing, false];
        }

        $stored = $this->storePhoto($photo);

        try {
            // The product and its delivery options are saved together, or not at all.
            $product = DB::transaction(function () use ($seller, $fields, $deliveryMethods, $submissionToken, $stored) {
                $product = new AgriculturalProduct($fields);
                $product->seller_id = $seller->id;
                $product->status = AgriculturalProduct::STATUS_PUBLISHED;
                $product->published_at = now();
                $product->version = 1;
                $product->submission_token = $submissionToken;
                $this->applyPhoto($product, $stored);
                $product->save();
                $product->deliveryOptions()->createMany(array_map(fn ($method) => ['method' => $method], $deliveryMethods));

                return $product;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Two identical submissions raced past the check above; the database let only one win.
            $this->photos->delete($this->pathsOf($stored));
            $winner = $this->findBySubmission($seller, $submissionToken);
            if ($winner) {
                return [$winner, false];
            }

            throw $e; // a different unique rule failed: a real error, not a duplicate
        } catch (Throwable $e) {
            $this->photos->delete($this->pathsOf($stored));

            throw $e;
        }

        return [$product, true];
    }

    /**
     * Save edited content, including price, stock, the bulk settings and the photo. The status is kept.
     * A new photo replaces the old one; $removePhoto removes it without a replacement.
     *
     * @param  array<string, mixed>  $fields
     * @param  list<string>  $deliveryMethods
     * @param  array<string, mixed>  $submittedText  shown back to the seller if their form was out of date
     */
    public function update(AgriculturalProduct $product, int $version, array $fields, array $deliveryMethods, ?UploadedFile $photo = null, bool $removePhoto = false, array $submittedText = []): AgriculturalProduct
    {
        $stored = $this->storePhoto($photo);

        try {
            [$product, $oldPaths] = DB::transaction(function () use ($product, $version, $fields, $deliveryMethods, $stored, $removePhoto, $submittedText) {
                $locked = $this->lockCurrent($product, $version, $submittedText);
                $oldPaths = ($stored !== null || $removePhoto) ? $locked->photoPaths() : [];

                $locked->fill($fields);
                if ($stored !== null) {
                    $this->applyPhoto($locked, $stored);
                } elseif ($removePhoto) {
                    $this->clearPhoto($locked);
                }
                $locked->version = $locked->version + 1;
                $locked->save();

                // Keep the chosen delivery options: remove the unticked ones, add the newly ticked ones.
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

        return $product;
    }

    /** Unpublish (hide from buyers) or publish again. A product with no stock may be published: it shows "Out of stock". */
    public function setVisibility(AgriculturalProduct $product, int $version, bool $publish): AgriculturalProduct
    {
        return DB::transaction(function () use ($product, $version, $publish) {
            $locked = $this->lockCurrent($product, $version);

            if ($publish) {
                $locked->status = AgriculturalProduct::STATUS_PUBLISHED;
                $locked->published_at ??= now(); // the first publication time is kept: republishing does not jump the queue
            } else {
                $locked->status = AgriculturalProduct::STATUS_UNPUBLISHED;
            }
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Remove (soft delete). The row and its private photo file are kept for future order history;
     * the product and its photo disappear everywhere in the app.
     */
    public function remove(AgriculturalProduct $product, int $version): void
    {
        DB::transaction(function () use ($product, $version) {
            $locked = $this->lockCurrent($product, $version);
            $locked->version = $locked->version + 1;
            $locked->save();
            $locked->delete();
        });
    }

    /**
     * Lock the product row until the transaction ends, and make sure the seller's page is not out of date.
     * A product removed meanwhile is "not found" (404); a changed one is a conflict (409).
     */
    private function lockCurrent(AgriculturalProduct $product, int $version, array $submittedText = []): AgriculturalProduct
    {
        $locked = AgriculturalProduct::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->version !== $version) {
            throw new AgriculturalProductConflictException($locked, $submittedText);
        }

        return $locked;
    }

    /**
     * Re-encode the upload into two private JPEGs. An unusable file becomes a normal form error on "photo".
     *
     * @return array{display_path: string, thumbnail_path: string, display_width: int, display_height: int}|null
     */
    private function storePhoto(?UploadedFile $photo): ?array
    {
        if ($photo === null) {
            return null;
        }

        try {
            return $this->photos->store($photo, AgriculturalProduct::PHOTO_DIRECTORY);
        } catch (UnusableCropPhotoException $e) {
            throw ValidationException::withMessages(['photo' => $e->userMessage()]);
        }
    }

    private function applyPhoto(AgriculturalProduct $product, ?array $stored): void
    {
        if ($stored === null) {
            return;
        }

        $product->photo_path = $stored['display_path'];
        $product->photo_thumbnail_path = $stored['thumbnail_path'];
        $product->photo_width = $stored['display_width'];
        $product->photo_height = $stored['display_height'];
    }

    private function clearPhoto(AgriculturalProduct $product): void
    {
        $product->photo_path = null;
        $product->photo_thumbnail_path = null;
        $product->photo_width = null;
        $product->photo_height = null;
    }

    /** @return list<string> */
    private function pathsOf(?array $stored): array
    {
        return $stored === null ? [] : [$stored['display_path'], $stored['thumbnail_path']];
    }

    private function findBySubmission(User $seller, string $submissionToken): ?AgriculturalProduct
    {
        return AgriculturalProduct::withTrashed()
            ->where('seller_id', $seller->id)
            ->where('submission_token', $submissionToken)
            ->first();
    }
}
