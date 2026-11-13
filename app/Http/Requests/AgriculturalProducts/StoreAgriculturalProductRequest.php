<?php

namespace App\Http\Requests\AgriculturalProducts;

use App\Http\Requests\Cattle\NormalisesBanglaDigits;
use App\Models\AgriculturalProduct;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "Add product" form (route seller.products.store). UpdateAgriculturalProductRequest reuses the content rules.
 * Limits live as constants on AgriculturalProduct, so the rules, the hints on the form and the tests use the
 * same numbers. The migration repeats the important ones as CHECK constraints.
 *
 * Bulk offers are optional: when "accepts_bulk_offers" is not ticked, the two bulk fields are ignored
 * (even if a forged request sends them) and both are saved as empty.
 */
class StoreAgriculturalProductRequest extends FormRequest
{
    use NormalisesBanglaDigits;

    /** Plain decimal: digits, optionally a point and decimals. No commas, signs, spaces, "e" or ৳. */
    protected const QUANTITY_FORMAT = '/^\d{1,7}(\.\d{1,3})?\z/';

    protected const PRICE_FORMAT = '/^\d{1,7}(\.\d{1,2})?\z/';

    /** Only sellers may add products (AgriculturalProductPolicy::create). */
    public function authorize(): bool|Response
    {
        return $this->user()->can('create', AgriculturalProduct::class);
    }

    /** A seller may type Bangla digits (১২৫০.৫০): they become 0–9 before the rules run. Nothing else is stripped. */
    protected function prepareForValidation(): void
    {
        $this->normaliseDigits(['unit_price', 'stock_quantity', 'bulk_min_quantity', 'bulk_min_unit_price']);
    }

    public function rules(): array
    {
        return $this->contentRules() + [
            'submission_token' => ['required', 'uuid'],
        ];
    }

    /** The fields shared by "add" and "edit". */
    protected function contentRules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'brand' => ['nullable', 'string', 'max:80'],
            'category' => ['required', 'string', Rule::in(AgriculturalProduct::CATEGORIES)],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'unit_code' => ['required', 'string', Rule::in(AgriculturalProduct::UNITS)],
            'unit_price' => ['bail', 'required', 'string', 'regex:'.self::PRICE_FORMAT, $this->between(AgriculturalProduct::MAX_PRICE, zeroAllowed: false)],
            // 0 is allowed: the product stays listed as "out of stock" until the seller restocks.
            'stock_quantity' => ['bail', 'required', 'string', 'regex:'.self::QUANTITY_FORMAT, $this->between(AgriculturalProduct::MAX_STOCK, zeroAllowed: true)],
            'accepts_bulk_offers' => ['nullable', 'boolean'],
            'bulk_min_quantity' => ['exclude_unless:accepts_bulk_offers,1', 'bail', 'required', 'string', 'regex:'.self::QUANTITY_FORMAT, $this->bulkQuantityRange()],
            'bulk_min_unit_price' => ['exclude_unless:accepts_bulk_offers,1', 'bail', 'required', 'string', 'regex:'.self::PRICE_FORMAT, $this->between(AgriculturalProduct::MAX_PRICE, zeroAllowed: false)],
        ];
    }

    /** Checks that need several fields. Each runs only when its fields passed their own format rules. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $errors = $validator->errors();
                $counted = in_array($this->input('unit_code'), AgriculturalProduct::COUNTED_UNITS, true);

                foreach (['stock_quantity', 'bulk_min_quantity'] as $field) {
                    // "40.000" is whole, "2.5" is not.
                    if ($counted && $this->isWellFormed($field, self::QUANTITY_FORMAT) && ! $errors->has($field)
                        && ! BigDecimal::of($this->input($field))->getFractionalPart()->isZero()) {
                        $errors->add($field, __('Bags, packets, bottles and pieces are counted in whole numbers. Remove the decimal part.'));
                    }
                }

                // The private minimum may equal the fixed price, but never be above it (F3 offers lie between the two).
                if ($this->bulkRequested() && ! $errors->has('unit_price') && ! $errors->has('bulk_min_unit_price')
                    && $this->isWellFormed('unit_price', self::PRICE_FORMAT) && $this->isWellFormed('bulk_min_unit_price', self::PRICE_FORMAT)
                    && BigDecimal::of($this->input('bulk_min_unit_price'))->isGreaterThan($this->input('unit_price'))) {
                    $errors->add('bulk_min_unit_price', __('The lowest bulk price cannot be higher than the fixed price per unit.'));
                }
            },
        ];
    }

    /** The product fields to save. Only keys from rules(), so seller_id, status or version can never be sent in. */
    public function productData(): array
    {
        $data = $this->safe()->only([
            'name', 'brand', 'category', 'description', 'unit_code', 'unit_price', 'stock_quantity',
            'bulk_min_quantity', 'bulk_min_unit_price',
        ]) + ['brand' => null];

        if (! $this->bulkRequested()) {
            $data['bulk_min_quantity'] = null;
            $data['bulk_min_unit_price'] = null;
        }

        return $data;
    }

    /** What the seller typed, to show back on the "changed elsewhere" page. Never the form token. */
    public function submittedText(): array
    {
        return $this->only(['name', 'brand', 'category', 'description', 'unit_code', 'unit_price', 'stock_quantity',
            'accepts_bulk_offers', 'bulk_min_quantity', 'bulk_min_unit_price']);
    }

    protected function bulkRequested(): bool
    {
        return (string) $this->input('accepts_bulk_offers') === '1';
    }

    private function isWellFormed(string $field, string $format): bool
    {
        return is_string($this->input($field)) && preg_match($format, $this->input($field)) === 1;
    }

    /** Exact decimal comparison (brick/math, shipped with Laravel): never converts money or stock to a float. */
    private function between(string $max, bool $zeroAllowed): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($max, $zeroAllowed) {
            $number = BigDecimal::of($value);
            if ($zeroAllowed ? $number->isNegative() : $number->isLessThanOrEqualTo(0)) {
                $fail($zeroAllowed ? __('The :attribute cannot be negative.') : __('The :attribute must be more than zero.'));
            } elseif ($number->isGreaterThan($max)) {
                $fail(__('The :attribute cannot be more than :max.', ['max' => number_format((int) $max)]));
            }
        };
    }

    private function bulkQuantityRange(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $number = BigDecimal::of($value);
            if ($number->isLessThan(AgriculturalProduct::MIN_BULK_QUANTITY)) {
                $fail(__('A bulk order starts at :min units or more. Smaller purchases always use the fixed price.', ['min' => AgriculturalProduct::MIN_BULK_QUANTITY]));
            } elseif ($number->isGreaterThan(AgriculturalProduct::MAX_STOCK)) {
                $fail(__('The :attribute cannot be more than :max.', ['max' => number_format((int) AgriculturalProduct::MAX_STOCK)]));
            }
        };
    }

    public function messages(): array
    {
        return [
            'unit_price.regex' => __('Enter the price as a plain number, like 1350 or 1350.50 (at most 2 decimal places, no commas or ৳).'),
            'bulk_min_unit_price.regex' => __('Enter the price as a plain number, like 1250 or 1250.50 (at most 2 decimal places, no commas or ৳).'),
            'stock_quantity.regex' => __('Enter the stock as a plain number, like 40 or 12.5 (at most 3 decimal places, no commas).'),
            'bulk_min_quantity.regex' => __('Enter the quantity as a plain number, like 10 or 12.5 (at most 3 decimal places, no commas).'),
            'bulk_min_quantity.required' => __('Enter the smallest quantity for a bulk offer, or untick “Accept bulk offers”.'),
            'bulk_min_unit_price.required' => __('Enter your lowest bulk price, or untick “Accept bulk offers”.'),
            'submission_token.required' => __('This form has expired. Open “Add product” again.'),
            'submission_token.uuid' => __('This form has expired. Open “Add product” again.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('product name'),
            'brand' => __('brand'),
            'category' => __('category'),
            'description' => __('description'),
            'unit_code' => __('unit'),
            'unit_price' => __('price per unit'),
            'stock_quantity' => __('stock'),
            'bulk_min_quantity' => __('bulk order quantity'),
            'bulk_min_unit_price' => __('lowest bulk price'),
        ];
    }
}
