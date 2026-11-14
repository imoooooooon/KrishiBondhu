<?php

namespace App\Http\Requests\MarketPrices;

use App\Http\Requests\Cattle\NormalisesBanglaDigits;
use App\Models\MarketPrice;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Record a price" form (route contributor.prices.store). Limits live as constants on MarketPrice, so the rules,
 * the hints on the form and the tests use the same numbers. The migration repeats them as CHECK constraints.
 *
 * Two kinds of entry (entry_kind):
 *  - actual:   a price the contributor saw, with its date (today or up to a year back). Vegetables and fish also need
 *              the session of the day (morning, afternoon, evening); every other product has one price per day, so
 *              the server sets "all_day" and ignores any session the form sends;
 *  - estimate: the contributor's estimate for TOMORROW. The date and session are set by the server
 *              (tomorrow, "all_day"); whatever the form sends for them is ignored.
 */
class StoreMarketPriceRequest extends FormRequest
{
    use NormalisesBanglaDigits;

    /** Plain decimal: digits, optionally a point and up to 2 decimals. No commas, signs, spaces, "e" or ৳. */
    protected const PRICE_FORMAT = '/^\d{1,6}(\.\d{1,2})?\z/';

    /** Only market price contributors may record prices (MarketPricePolicy::create). */
    public function authorize(): bool|Response
    {
        return $this->user()->can('create', MarketPrice::class);
    }

    /** A contributor may type Bangla digits (৩৫.৫০, ২০২৬-১১-১৪): they become 0–9 before the rules run. */
    protected function prepareForValidation(): void
    {
        $this->normaliseDigits(['unit_price', 'price_date']);
    }

    public function rules(): array
    {
        return [
            'entry_kind' => ['required', 'string', Rule::in(MarketPrice::KINDS)],
            'market_id' => ['required', 'integer', Rule::exists('markets', 'id')],
            'item_code' => ['required', 'string', Rule::in(MarketPrice::itemCodes())],
            'price_type' => ['required', 'string', Rule::in(MarketPrice::TYPES)],
            'unit_price' => ['bail', 'required', 'string', 'regex:'.self::PRICE_FORMAT, $this->positiveAtMost(MarketPrice::MAX_PRICE)],
            // An actual price: the day it applied (today or up to a year back, Bangladesh calendar) and the session.
            'price_date' => ['exclude_if:entry_kind,estimate', 'required', 'date_format:Y-m-d',
                'after_or_equal:'.MarketPrice::earliestDate(), 'before_or_equal:'.MarketPrice::today()],
            'price_session' => [Rule::excludeIf(! $this->needsSession()), 'required', 'string', Rule::in(MarketPrice::SESSIONS)],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    /** The fields to save. Only keys from rules(), so contributor_id or version can never be sent in. */
    public function priceData(): array
    {
        $data = $this->safe()->only(['market_id', 'item_code', 'price_type', 'unit_price', 'price_date', 'price_session', 'note'])
            + ['note' => null];

        if ($this->validated('entry_kind') === 'estimate') {
            return ['price_date' => MarketPrice::tomorrow(), 'price_session' => MarketPrice::DAY_SESSION, 'is_estimate' => true] + $data;
        }

        return $data + ['price_session' => MarketPrice::DAY_SESSION, 'is_estimate' => false];
    }

    /** Only an actual price of a vegetable or fish has a session; everything else is a whole-day price. */
    private function needsSession(): bool
    {
        return $this->input('entry_kind') === 'actual' && MarketPrice::changesDuringDay($this->input('item_code'));
    }

    /** Exact decimal comparison (brick/math, shipped with Laravel): never converts money to a float. */
    private function positiveAtMost(string $max): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($max) {
            $number = BigDecimal::of($value);
            if ($number->isLessThanOrEqualTo(0)) {
                $fail(__('The :attribute must be more than zero.'));
            } elseif ($number->isGreaterThan($max)) {
                $fail(__('The :attribute cannot be more than :max.', ['max' => number_format((int) $max)]));
            }
        };
    }

    public function messages(): array
    {
        return [
            'unit_price.regex' => __('Enter the price as a plain number, like 35 or 35.50 (at most 2 decimal places, no commas or ৳).'),
            'price_date.before_or_equal' => __('The price date cannot be in the future.'),
            'price_date.after_or_equal' => __('The price date can be at most one year ago.'),
            'price_date.date_format' => __('Choose a valid date.'),
            'market_id.exists' => __('Choose a market from the list.'),
            'price_session.required' => __('Choose the time of day this price applied.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'market_id' => __('market'),
            'item_code' => __('product'),
            'price_type' => __('price type'),
            'unit_price' => __('price per unit'),
            'price_date' => __('price date'),
            'price_session' => __('time of day'),
            'entry_kind' => __('kind of price'),
            'note' => __('note'),
        ];
    }
}
