<?php

namespace App\Services;

use App\Models\MarketPrice;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Every write to market prices (F11). Controllers call this; nothing else creates or changes price rows.
 *
 * History rule: a new price is always a new row, and rows are never deleted. Each contributor has at most one row
 * per market + crop + price type + day (a unique key in the database).
 */
class MarketPriceService
{
    /**
     * Record one price observation. Returns the row and whether THIS request created it.
     *
     * If the contributor already recorded that market + crop + type + day:
     * - with the same price: it is the same form sent twice (double tap, retry on a slow connection) -> the existing
     *   row is returned and nothing is added;
     * - with a different price: refused with a form error. Changing a recorded price is a correction (logged), not a
     *   second entry for the same day.
     *
     * @param  array<string, mixed>  $fields  validated MarketPrice fields
     * @return array{0: MarketPrice, 1: bool}
     */
    public function record(User $contributor, array $fields): array
    {
        $existing = $this->findSameDay($contributor, $fields);
        if ($existing) {
            return [$this->sameOrRefuse($existing, $fields), false];
        }

        try {
            $price = new MarketPrice($fields);
            $price->contributor_id = $contributor->id;
            $price->version = 1;
            $price->save();
        } catch (UniqueConstraintViolationException $e) {
            // Two identical requests raced past the check above; the database let only one win.
            $winner = $this->findSameDay($contributor, $fields);
            if ($winner) {
                return [$this->sameOrRefuse($winner, $fields), false];
            }

            throw $e; // a different unique rule failed: a real error, not a duplicate
        }

        return [$price, true];
    }

    private function findSameDay(User $contributor, array $fields): ?MarketPrice
    {
        return MarketPrice::query()
            ->recordedBy($contributor)
            ->where('market_id', $fields['market_id'])
            ->where('crop_code', $fields['crop_code'])
            ->where('price_type', $fields['price_type'])
            ->whereDate('price_date', $fields['price_date'])
            ->first();
    }

    /** The same price again is a resent form; a different price for the same day is refused. */
    private function sameOrRefuse(MarketPrice $existing, array $fields): MarketPrice
    {
        if (BigDecimal::of((string) $existing->price_per_kg)->isEqualTo((string) $fields['price_per_kg'])) {
            return $existing;
        }

        throw ValidationException::withMessages([
            'price_date' => __('You already recorded this crop at this market for that day (৳ :price per kg). To change it, correct that entry instead of adding a second one.', [
                'price' => $existing->price_per_kg,
            ]),
        ]);
    }
}
