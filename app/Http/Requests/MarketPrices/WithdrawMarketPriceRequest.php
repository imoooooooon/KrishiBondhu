<?php

namespace App\Http\Requests\MarketPrices;

use App\Models\MarketPrice;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * "Withdraw this price" (route contributor.prices.destroy, DELETE). Hides the entry everywhere but keeps it in the
 * database with the reason. Needs the owner within the correction window, the explicit confirmation, a reason, and the
 * version the page was opened with.
 */
class WithdrawMarketPriceRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('delete', $this->price());
    }

    public function rules(): array
    {
        return [
            'confirm_withdraw' => ['accepted'],
            'version' => ['required', 'integer', 'min:1'],
            'withdrawal_reason' => ['required', 'string', 'min:5', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_withdraw.accepted' => __('Confirm that you want to withdraw this price.'),
        ];
    }

    public function attributes(): array
    {
        return ['withdrawal_reason' => __('reason for withdrawing')];
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    public function reason(): string
    {
        return $this->validated('withdrawal_reason');
    }

    public function price(): MarketPrice
    {
        return $this->route('price');
    }
}
