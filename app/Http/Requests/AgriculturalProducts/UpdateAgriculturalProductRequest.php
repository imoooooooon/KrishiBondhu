<?php

namespace App\Http\Requests\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Route seller.products.update (PATCH). One route, three operations chosen by the hidden "operation" field:
 *  - save:      the edit form (same rules as "add", plus "remove_photo");
 *  - publish:   show the product to buyers again;
 *  - unpublish: hide it from buyers.
 * Every operation sends "version", the number the page was opened with, to detect stale tabs.
 */
class UpdateAgriculturalProductRequest extends StoreAgriculturalProductRequest
{
    public const OPERATIONS = ['save', 'publish', 'unpublish'];

    /** Owner only: a non-seller gets 403, another seller 404 (AgriculturalProductPolicy::update). Runs before validation. */
    public function authorize(): bool|Response
    {
        return Gate::inspect('update', $this->product());
    }

    public function rules(): array
    {
        $rules = [
            'operation' => ['required', 'string', Rule::in(self::OPERATIONS)],
            'version' => ['required', 'integer', 'min:1'],
        ];

        if ($this->input('operation') !== 'save') {
            return $rules; // publish / unpublish accept nothing else
        }

        return $rules + $this->contentRules() + [
            'remove_photo' => ['nullable', 'boolean'], // a new upload replaces the photo; this removes it without a new one
        ];
    }

    /** "Remove the photo" was ticked and no new photo was chosen (a new photo always wins). */
    public function removePhoto(): bool
    {
        return $this->photo() === null && (string) $this->validated('remove_photo') === '1';
    }

    public function after(): array
    {
        return $this->input('operation') === 'save' ? parent::after() : [];
    }

    public function operation(): string
    {
        return $this->validated('operation');
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    /** The product from the URL (/seller/products/{product}). */
    public function product(): AgriculturalProduct
    {
        return $this->route('product');
    }
}
