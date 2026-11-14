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
 * per market + product + price type + day + session + kind (actual or estimate), the same as the unique key in the
 * database. Vegetables and fish have morning/afternoon/evening sessions; other products and estimates use "all_day".
 */
class MarketPriceService
{
    /**
     * Record one price observation. Returns the row and whether THIS request created it.
     *
     * If the contributor already recorded that market + product + type + day + session + kind:
     * - with the same price: it is the same form sent twice (double tap, retry on a slow connection) -> the existing
     *   row is returned and nothing is added;
     * - with a different price: refused with a form error. Changing a recorded price is a correction (logged), not a
     *   second entry for the same session.
     *
     * @param  array<string, mixed>  $fields  validated MarketPrice fields
     * @return array{0: MarketPrice, 1: bool}
     */
    public function record(User $contributor, array $fields): array
    {
        $existing = $this->findSameSession($contributor, $fields);
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
            $winner = $this->findSameSession($contributor, $fields);
            if ($winner) {
                return [$this->sameOrRefuse($winner, $fields), false];
            }

            throw $e; // a different unique rule failed: a real error, not a duplicate
        }

        return [$price, true];
    }

    private function findSameSession(User $contributor, array $fields): ?MarketPrice
    {
        return MarketPrice::query()
            ->recordedBy($contributor)
            ->where('market_id', $fields['market_id'])
            ->where('item_code', $fields['item_code'])
            ->where('price_type', $fields['price_type'])
            ->whereDate('price_date', $fields['price_date'])
            ->where('price_session', $fields['price_session'])
            ->where('is_estimate', $fields['is_estimate']) // an estimate and the actual price for the same day are different entries
            ->first();
    }

    /** The same price again is a resent form; a different price for the same session is refused. */
    private function sameOrRefuse(MarketPrice $existing, array $fields): MarketPrice
    {
        if (BigDecimal::of((string) $existing->unit_price)->isEqualTo((string) $fields['unit_price'])) {
            return $existing;
        }

        throw ValidationException::withMessages([
            'unit_price' => __('You already recorded this product at this market for that time (৳ :price per :unit). To change it, correct that entry instead of adding a second one.', [
                'price' => $existing->unit_price,
                'unit' => $existing->unitLabel(),
            ]),
        ]);
    }
}
