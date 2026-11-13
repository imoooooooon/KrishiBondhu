<?php

namespace App\Services;

use App\Exceptions\AgriculturalProductConflictException;
use App\Models\AgriculturalProduct;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Every write to a farm-supply product (F25). Controllers call this; nothing else changes product rows.
 *
 * The pattern used in every change:
 *  1. In a transaction, lock the product row (SELECT ... FOR UPDATE).
 *  2. Compare the version the seller's page was opened with. Different = someone changed it meanwhile
 *     (another tab, another phone): refuse with a 409 instead of overwriting newer stock or prices.
 *  3. Change the row and add 1 to the version.
 *
 * Contract for F5 (orders) and F3 (offers), which will also change stock: lock the row the same way and
 * add 1 to the version, so this service's stale-tab check also sees their changes.
 */
class AgriculturalProductService
{
    /**
     * Add and publish a new product. Returns the product and whether it was created by THIS request
     * (false = the same form was already submitted, e.g. a double tap or a retry on a bad connection).
     *
     * @param  array<string, mixed>  $fields  validated AgriculturalProduct fields
     * @return array{0: AgriculturalProduct, 1: bool}
     */
    public function create(User $seller, array $fields, string $submissionToken): array
    {
        // Same seller + same form token = the same submission. Includes removed products, so a removed
        // product is never brought back by re-sending an old form.
        $existing = $this->findBySubmission($seller, $submissionToken);
        if ($existing) {
            return [$existing, false];
        }

        try {
            $product = new AgriculturalProduct($fields);
            $product->seller_id = $seller->id;
            $product->status = AgriculturalProduct::STATUS_PUBLISHED;
            $product->published_at = now();
            $product->version = 1;
            $product->submission_token = $submissionToken;
            $product->save();
        } catch (UniqueConstraintViolationException $e) {
            // Two identical submissions raced past the check above; the database let only one win.
            $winner = $this->findBySubmission($seller, $submissionToken);
            if ($winner) {
                return [$winner, false];
            }

            throw $e; // a different unique rule failed: a real error, not a duplicate
        }

        return [$product, true];
    }

    /**
     * Save edited content, including price, stock and the bulk settings. The status (published or not) is kept.
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $submittedText  shown back to the seller if their form was out of date
     */
    public function update(AgriculturalProduct $product, int $version, array $fields, array $submittedText = []): AgriculturalProduct
    {
        return DB::transaction(function () use ($product, $version, $fields, $submittedText) {
            $locked = $this->lockCurrent($product, $version, $submittedText);
            $locked->fill($fields);
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked;
        });
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

    /** Remove (soft delete). The row is kept for future order history; the product disappears everywhere in the app. */
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

    private function findBySubmission(User $seller, string $submissionToken): ?AgriculturalProduct
    {
        return AgriculturalProduct::withTrashed()
            ->where('seller_id', $seller->id)
            ->where('submission_token', $submissionToken)
            ->first();
    }
}
