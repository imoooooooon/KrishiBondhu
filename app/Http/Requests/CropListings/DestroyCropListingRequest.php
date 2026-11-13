<?php

namespace App\Http\Requests\CropListings;

use App\Models\CropListing;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * "Remove listing" (route farmer.listings.destroy, DELETE). Needs the owner, the explicit confirmation
 * checkbox from the confirmation box, and the version the page was opened with.
 */
class DestroyCropListingRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('delete', $this->listing());
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
            'confirm_remove.accepted' => __('Confirm that you want to remove this listing.'),
        ];
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    public function listing(): CropListing
    {
        return $this->route('listing');
    }
}
