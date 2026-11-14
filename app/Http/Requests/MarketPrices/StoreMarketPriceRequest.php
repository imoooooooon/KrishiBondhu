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
        $this->normaliseDigits(['price_per_kg', 'price_date']);
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'integer', Rule::exists('markets', 'id')],
            'crop_code' => ['required', 'string', Rule::in(MarketPrice::CROPS)],
            'price_type' => ['required', 'string', Rule::in(MarketPrice::TYPES)],
            'price_per_kg' => ['bail', 'required', 'string', 'regex:'.self::PRICE_FORMAT, $this->positiveAtMost(MarketPrice::MAX_PRICE)],
            // The day the price applied: today or up to a year back, on the Bangladesh calendar.
            'price_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.MarketPrice::earliestDate(), 'before_or_equal:'.MarketPrice::today()],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    /** The fields to save. Only keys from rules(), so contributor_id or version can never be sent in. */
    public function priceData(): array
    {
        return $this->safe()->only(['market_id', 'crop_code', 'price_type', 'price_per_kg', 'price_date', 'note']) + ['note' => null];
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
            'price_per_kg.regex' => __('Enter the price per kg as a plain number, like 35 or 35.50 (at most 2 decimal places, no commas or ৳).'),
            'price_date.before_or_equal' => __('The price date cannot be in the future.'),
            'price_date.after_or_equal' => __('The price date can be at most one year ago.'),
            'price_date.date_format' => __('Choose a valid date.'),
            'market_id.exists' => __('Choose a market from the list.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'market_id' => __('market'),
            'crop_code' => __('crop'),
            'price_type' => __('price type'),
            'price_per_kg' => __('price per kg'),
            'price_date' => __('price date'),
            'note' => __('note'),
        ];
    }
}
