<?php

namespace App\Services;

use App\Models\MarketPrice;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Market price rules (F11). Every write goes through here (nothing else creates or changes price rows), and so do
 * the non-trivial reads of the public price page: the latest price, yesterday's price and tomorrow's estimate.
 *
 * History rule: a new price is always a new row, and rows are never deleted. Each contributor has at most one row
 * per market + product + price type + day + session + kind (actual or estimate), the same as the unique key in the
 * database. Vegetables and fish have morning/afternoon/evening sessions; other products and estimates use "all_day".
 */
class MarketPriceService
{
    /** Rows per page on the public price page. */
    public const BOARD_PER_PAGE = 20;

    /** How many recent entries the public page shows for one product at one market. */
    public const RECENT_LIMIT = 10;

    /**
     * "Latest first" within one day: evening, then afternoon, then morning (whole-day prices have no rival sessions).
     * Used inside SQL ORDER BY, so it is a fixed expression, never built from user input.
     */
    private const SESSION_RANK = "CASE price_session WHEN 'evening' THEN 3 WHEN 'afternoon' THEN 2 WHEN 'morning' THEN 1 ELSE 0 END";

    /**
     * The public price board: one row per market + product + price type that has an actual price, ordered like the
     * product list, then by market. Each row is an array:
     *  - latest:    the latest actual price (latest date, then latest session, then latest entry);
     *  - today:     the latest price if it is from today (Bangladesh calendar), otherwise null;
     *  - yesterday: yesterday's latest actual price, or null;
     *  - estimate:  the latest estimate for tomorrow, or null;
     *  - change:    today minus yesterday as an exact decimal string ("+2.50", "-1.00"), or null;
     *  - percent:   that change in percent with one decimal ("+7.1"), or null;
     *  - direction: "up", "down" or "same" when there is a change to show, otherwise null.
     * Three queries in total, whatever the page size (the latest prices, then yesterday and tomorrow for the page).
     *
     * @param  array{group?: string, item?: string, market?: int, type?: string}  $filters
     */
    public function board(array $filters): LengthAwarePaginator
    {
        $codes = MarketPrice::itemCodes();

        $page = MarketPrice::query()
            ->fromSub($this->latestPerKey($filters, estimate: false), 'market_prices')
            ->where('rn', 1)
            ->with('market')
            ->orderByRaw('FIELD(item_code, '.implode(',', array_fill(0, count($codes), '?')).')', $codes)
            ->orderBy('market_id')
            ->orderBy('price_type')
            ->paginate(self::BOARD_PER_PAGE)
            ->withQueryString();

        $marketIds = $page->getCollection()->pluck('market_id')->unique()->all();
        $itemCodes = $page->getCollection()->pluck('item_code')->unique()->all();
        $today = MarketPrice::today();
        $yesterday = $this->keyed($this->latestOn(Carbon::parse($today)->subDay()->toDateString(), false, $marketIds, $itemCodes));
        $estimates = $this->keyed($this->latestOn(MarketPrice::tomorrow(), true, $marketIds, $itemCodes));

        return $page->through(function (MarketPrice $latest) use ($today, $yesterday, $estimates) {
            $key = $this->key($latest);
            $todayPrice = $latest->price_date->toDateString() === $today ? $latest : null;
            $yesterdayPrice = $yesterday[$key] ?? null;
            [$change, $percent, $direction] = $this->change($todayPrice, $yesterdayPrice);

            return [
                'latest' => $latest,
                'today' => $todayPrice,
                'yesterday' => $yesterdayPrice,
                'estimate' => $estimates[$key] ?? null,
                'change' => $change,
                'percent' => $percent,
                'direction' => $direction,
            ];
        });
    }

    /**
     * The recent entries for one product at one market, newest first: actual prices of both types and estimates
     * (marked). Shows that history is kept; charts and time periods are F14's.
     *
     * @return Collection<int, MarketPrice>
     */
    public function recent(int $marketId, string $itemCode): Collection
    {
        return MarketPrice::query()
            ->where('market_id', $marketId)
            ->where('item_code', $itemCode)
            ->orderByDesc('price_date')
            ->orderByRaw(self::SESSION_RANK.' DESC')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get();
    }

    /**
     * Every price row numbered within its market + product + type, latest first, so rn = 1 is the latest.
     * ROW_NUMBER() is a MySQL 8 window function: one pass, no query per row.
     */
    private function latestPerKey(array $filters, bool $estimate): Builder
    {
        return MarketPrice::query()
            ->select('market_prices.*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY market_id, item_code, price_type ORDER BY price_date DESC, '
                .self::SESSION_RANK.' DESC, id DESC) AS rn')
            ->where('is_estimate', $estimate)
            ->when($filters['group'] ?? null, fn (Builder $query, string $group) => $query->whereIn('item_code',
                array_keys(array_filter(MarketPrice::ITEMS, fn (array $meta) => $meta[0] === $group))))
            ->when($filters['item'] ?? null, fn (Builder $query, string $item) => $query->where('item_code', $item))
            ->when($filters['market'] ?? null, fn (Builder $query, int $market) => $query->where('market_id', $market))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('price_type', $type));
    }

    /** @return Collection<int, MarketPrice> the latest price per market + product + type on one date */
    private function latestOn(string $date, bool $estimate, array $marketIds, array $itemCodes): Collection
    {
        if ($marketIds === []) {
            return new Collection;
        }

        $ranked = $this->latestPerKey([], $estimate)
            ->whereDate('price_date', $date)
            ->whereIn('market_id', $marketIds)
            ->whereIn('item_code', $itemCodes);

        return MarketPrice::query()->fromSub($ranked, 'market_prices')->where('rn', 1)->get();
    }

    /** @return array<string, MarketPrice> */
    private function keyed(Collection $prices): array
    {
        return $prices->keyBy(fn (MarketPrice $price) => $this->key($price))->all();
    }

    private function key(MarketPrice $price): string
    {
        return $price->market_id.'|'.$price->item_code.'|'.$price->price_type;
    }

    /**
     * Today minus yesterday, exactly (brick/math, never floats), and the change in percent rounded half up to one decimal.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string} change, percent, direction
     */
    private function change(?MarketPrice $today, ?MarketPrice $yesterday): array
    {
        if ($today === null || $yesterday === null) {
            return [null, null, null];
        }

        $now = BigDecimal::of((string) $today->unit_price);
        $before = BigDecimal::of((string) $yesterday->unit_price);
        $difference = $now->minus($before);
        $percent = $difference->multipliedBy(100)->dividedBy($before, 1, RoundingMode::HalfUp);
        $sign = fn (BigDecimal $number) => $number->isPositive() ? '+'.$number : (string) $number;

        $direction = $difference->isPositive() ? 'up' : ($difference->isNegative() ? 'down' : 'same');

        return [$sign($difference->toScale(2)), $sign($percent), $direction];
    }

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
