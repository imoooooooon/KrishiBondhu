<?php

namespace App\Http\Requests\MarketPrices;

use App\Models\MarketPrice;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * "Correct this price" (route contributor.prices.update, PATCH). Only the price and the note can be corrected; a wrong
 * market, product, date or time is fixed by withdrawing the entry and recording it again. A reason is required and the
 * old values are logged. "version" is the number the page was opened with, to detect a correction from an old tab.
 */
class UpdateMarketPriceRequest extends StoreMarketPriceRequest
{
    /** Own entry within the correction window (MarketPricePolicy::update). Runs before validation. */
    public function authorize(): bool|Response
    {
        return Gate::inspect('update', $this->price());
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseDigits(['unit_price']);
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'unit_price' => ['bail', 'required', 'string', 'regex:'.self::PRICE_FORMAT, $this->positiveAtMost(MarketPrice::MAX_PRICE)],
            'note' => ['nullable', 'string', 'max:200'],
            'reason' => ['required', 'string', 'min:5', 'max:200'],
        ];
    }

    /** @return array{unit_price: string, note: ?string} */
    public function correction(): array
    {
        return $this->safe()->only(['unit_price', 'note']) + ['note' => null];
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    public function attributes(): array
    {
        return parent::attributes() + ['reason' => __('reason for the correction')];
    }

    /** The entry from the URL (/contributor/prices/{price}). */
    public function price(): MarketPrice
    {
        return $this->route('price');
    }
}
