<?php

namespace App\Http\Requests\MarketPrices;

use App\Models\MarketPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The filters on the public price page (route market-prices.index). They arrive in the URL (GET), e.g.
 * /market-prices?group=vegetable&market=1&type=wholesale, so a view can be refreshed or shared.
 * Every filter is optional. Anyone may read prices, so there is no authorize() rule.
 */
class SearchMarketPricesRequest extends FormRequest
{
    /** A wrong filter (typed into the URL) sends the visitor back to the plain price page with the error shown. */
    protected $redirectRoute = 'market-prices.index';

    public function rules(): array
    {
        return [
            'group' => ['nullable', 'string', Rule::in(MarketPrice::GROUPS)],
            'item' => ['nullable', 'string', Rule::in(MarketPrice::itemCodes())],
            'market' => ['nullable', 'integer', Rule::exists('markets', 'id')],
            'type' => ['nullable', 'string', Rule::in(MarketPrice::TYPES)],
        ];
    }

    /** @return array{group?: string, item?: string, market?: int, type?: string} the filters in use */
    public function filters(): array
    {
        $filters = array_filter($this->validated(), fn ($value) => $value !== null && $value !== '');
        if (isset($filters['market'])) {
            $filters['market'] = (int) $filters['market'];
        }

        return $filters;
    }

    public function attributes(): array
    {
        return [
            'group' => __('product group'),
            'item' => __('product'),
            'market' => __('market'),
            'type' => __('price type'),
        ];
    }
}
