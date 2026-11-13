<?php

namespace App\Http\Requests\CropListings;

use App\Models\CropListing;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Route farmer.listings.update (PATCH). One route, three operations chosen by the hidden "operation" field:
 *  - save:      the edit form (same rules as "add", photos optional because existing ones can be kept);
 *  - publish:   show the listing to buyers again (the service re-checks the stored listing);
 *  - unpublish: hide it from buyers.
 * Every operation sends "version", the number the page was opened with, to detect stale tabs.
 */
class UpdateCropListingRequest extends StoreCropListingRequest
{
    public const OPERATIONS = ['save', 'publish', 'unpublish'];

    /** Owner only: a non-farmer gets 403, another farmer 404 (CropListingPolicy::update). Runs before validation. */
    public function authorize(): bool|Response
    {
        return Gate::inspect('update', $this->listing());
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
            'photos' => ['nullable', 'array', 'max:'.CropListing::MAX_PHOTOS],
            'photos.*' => $this->photoRules(),
            'remove_photo_ids' => ['nullable', 'array', 'max:'.CropListing::MAX_PHOTOS],
            'remove_photo_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function after(): array
    {
        if ($this->input('operation') !== 'save') {
            return [];
        }

        return [
            ...parent::after(),
            // Early, friendly count check. CropListingService checks again under the row lock, which is the real guarantee.
            function (Validator $validator) {
                $current = $this->listing()->photos()->pluck('id')->all();
                $removing = array_intersect($current, array_map('intval', (array) $this->input('remove_photo_ids', [])));
                $final = count($current) - count($removing) + count($this->uploads());
                if ($final < CropListing::MIN_PHOTOS) {
                    $validator->errors()->add('photos', __('Keep at least one photo, or add a new one.'));
                } elseif ($final > CropListing::MAX_PHOTOS) {
                    $validator->errors()->add('photos', __('A listing can have at most :max photos. Remove some before adding more.', ['max' => CropListing::MAX_PHOTOS]));
                }
            },
        ];
    }

    public function operation(): string
    {
        return $this->validated('operation');
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    /** @return list<int> */
    public function removePhotoIds(): array
    {
        return array_values(array_map('intval', $this->validated('remove_photo_ids') ?? []));
    }

    /** The listing from the URL (/farmer/listings/{listing}). */
    public function listing(): CropListing
    {
        return $this->route('listing');
    }
}
