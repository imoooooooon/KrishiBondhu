<?php

namespace App\Http\Requests\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * "Remove product" (route seller.products.destroy, DELETE). Needs the owner, the explicit confirmation
 * from the confirmation box, and the version the page was opened with.
 */
class DestroyAgriculturalProductRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('delete', $this->product());
    }

    public function rules(): array
    {
        return [
            'confirm_remove' => ['accepted'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_remove.accepted' => __('Confirm that you want to remove this product.'),
        ];
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    public function product(): AgriculturalProduct
    {
        return $this->route('product');
    }
}
