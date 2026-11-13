<?php

namespace App\Http\Requests\AgriculturalProducts;

use App\Http\Requests\Cattle\NormalisesBanglaDigits;
use App\Models\AgriculturalProduct;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The search filters on the public shop (route shop.index). They arrive in the URL (GET), e.g.
 * /shop?q=urea&category=fertilizer&max_price=1500, so a search can be refreshed or shared.
 * Every filter is optional. Anyone may search, so there is no authorize() rule.
 */
class SearchShopRequest extends FormRequest
{
    use NormalisesBanglaDigits;

    private const PRICE_FORMAT = '/^\d{1,7}(\.\d{1,2})?\z/';

    /** A wrong filter (typed into the URL) sends the visitor back to the plain shop with the error shown. */
    protected $redirectRoute = 'shop.index';

    protected function prepareForValidation(): void
    {
        $this->normaliseDigits(['min_price', 'max_price']);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::in(AgriculturalProduct::CATEGORIES)],
            'min_price' => ['nullable', 'string', 'regex:'.self::PRICE_FORMAT],
            'max_price' => ['nullable', 'string', 'regex:'.self::PRICE_FORMAT],
            'in_stock' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $min = $this->input('min_price');
                $max = $this->input('max_price');
                if (is_string($min) && is_string($max) && ! $validator->errors()->hasAny(['min_price', 'max_price'])
                    && BigDecimal::of($min)->isGreaterThan($max)) {
                    $validator->errors()->add('max_price', __('The highest price must be at least the lowest price.'));
                }
            },
        ];
    }

    /** @return array{q?: string, category?: string, min_price?: string, max_price?: string, in_stock?: bool} the filters in use */
    public function filters(): array
    {
        $filters = array_filter($this->validated(), fn ($value) => $value !== null && $value !== '');
        if (isset($filters['in_stock'])) {
            $filters['in_stock'] = (string) $filters['in_stock'] === '1';
        }

        return $filters;
    }

    public function messages(): array
    {
        return [
            'min_price.regex' => __('Enter the price as a plain number, like 500 or 1350.50 (no commas or ৳).'),
            'max_price.regex' => __('Enter the price as a plain number, like 500 or 1350.50 (no commas or ৳).'),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => __('keyword'),
            'category' => __('category'),
            'min_price' => __('lowest price'),
            'max_price' => __('highest price'),
        ];
    }
}
