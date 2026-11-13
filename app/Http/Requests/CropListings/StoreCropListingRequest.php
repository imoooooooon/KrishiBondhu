<?php

namespace App\Http\Requests\CropListings;

use App\Models\CropListing;
use App\Services\CropListingPhotoService;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "Add crop listing" form (route farmer.listings.store). UpdateCropListingRequest reuses the content rules.
 * Limits come from F1 spec §9 and live as constants on CropListing / CropListingPhotoService,
 * so the rules, the hints on the form and the tests use the same numbers.
 */
class StoreCropListingRequest extends FormRequest
{
    /** Plain decimal: digits, optionally a point and decimals. No commas, signs, spaces, "e" or ৳. */
    private const QUANTITY_FORMAT = '/^\d{1,7}(\.\d{1,3})?\z/';

    private const PRICE_FORMAT = '/^\d{1,7}(\.\d{1,2})?\z/';

    /** Only farmers may publish (CropListingPolicy::create). */
    public function authorize(): bool|Response
    {
        return $this->user()->can('create', CropListing::class);
    }

    /**
     * Before validation: a farmer may type Bangla digits (১২৫০.৫০). Turn them into 0–9 so the
     * rules and the database see "1250.50". Nothing else is changed: commas or ৳ are rejected, not stripped.
     */
    protected function prepareForValidation(): void
    {
        $toAscii = array_combine(CropListing::BANGLA_DIGITS, str_split('0123456789'));
        $normalised = [];
        foreach (['quantity', 'price_per_unit', 'harvest_date'] as $key) {
            if (is_string($this->input($key))) {
                $normalised[$key] = trim(strtr($this->input($key), $toAscii));
            }
        }
        $this->merge($normalised);
    }

    public function rules(): array
    {
        return $this->contentRules() + [
            'submission_token' => ['required', 'uuid'],
            'photos' => ['required', 'array', 'min:'.CropListing::MIN_PHOTOS, 'max:'.CropListing::MAX_PHOTOS],
            'photos.*' => $this->photoRules(),
        ];
    }

    /** The fields shared by "add" and "edit". */
    protected function contentRules(): array
    {
        return [
            'crop_name' => ['required', 'string', 'min:2', 'max:100'],
            'quality_description' => ['required', 'string', 'min:5', 'max:500'],
            // The actual harvest date: today or earlier in Bangladesh. 2000 is only a sanity floor for typos.
            'harvest_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:'.CropListing::today()],
            'quantity' => ['bail', 'required', 'string', 'regex:'.self::QUANTITY_FORMAT, $this->positiveAtMost(CropListing::MAX_QUANTITY)],
            'unit_code' => ['required', 'string', Rule::in(CropListing::UNITS)],
            'price_per_unit' => ['bail', 'required', 'string', 'regex:'.self::PRICE_FORMAT, $this->positiveAtMost(CropListing::MAX_PRICE)],
            'public_area' => ['required', 'string', 'min:2', 'max:120'],
            'delivery_options' => ['required', 'array', 'min:1', 'max:'.count(CropListing::DELIVERY_METHODS)],
            'delivery_options.*' => ['required', 'string', 'distinct', Rule::in(CropListing::DELIVERY_METHODS)],
            'delivery_notes' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * One photo: a real upload, at most 5 MB, JPEG or PNG by extension AND by content, at most 6000 px a side.
     * The service decodes it afterwards, which is the real proof that it is an image.
     */
    protected function photoRules(): array
    {
        $side = CropListingPhotoService::MAX_SIDE;

        return [
            'bail', 'file', 'max:'.CropListingPhotoService::MAX_KILOBYTES,
            'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png',
            "dimensions:max_width={$side},max_height={$side}",
        ];
    }

    /** Checks that need several fields, or that the simple rules cannot express. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $quantity = $this->input('quantity');
                // The format is already checked, so the part after "." is digits only: "40.000" is whole, "2.5" is not.
                if ($this->input('unit_code') === 'piece' && is_string($quantity) && preg_match(self::QUANTITY_FORMAT, $quantity)
                    && rtrim(strstr($quantity, '.') ?: '', '.0') !== '') {
                    $validator->errors()->add('quantity', __('Pieces are counted in whole numbers. Remove the decimal part.'));
                }

                // Reject photos with too many pixels before any decoding work starts.
                foreach ($this->uploads() as $i => $file) {
                    $size = $file->isValid() ? @getimagesize($file->getRealPath()) : false;
                    if ($size && $size[0] * $size[1] > CropListingPhotoService::MAX_PIXELS) {
                        $validator->errors()->add('photos.'.$i, __('Photo :number is too large in pixels. Use a photo of at most 12 megapixels.', ['number' => $i + 1]));
                    }
                }
            },
        ];
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

    /** The listing fields to save. Only keys from rules(), so farmer_id, status or version can never be sent in. */
    public function listingData(): array
    {
        return $this->safe()->only([
            'crop_name', 'quality_description', 'harvest_date', 'quantity', 'unit_code',
            'price_per_unit', 'public_area', 'delivery_notes',
        ]) + ['delivery_notes' => null];
    }

    /** @return list<string> */
    public function deliveryMethods(): array
    {
        return array_values(array_unique($this->validated('delivery_options')));
    }

    /** @return list<UploadedFile> */
    public function uploads(): array
    {
        $files = $this->file('photos', []);

        return is_array($files) ? array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile)) : [];
    }

    /** The text the farmer typed, to show back on the "changed elsewhere" page. Never files or tokens. */
    public function submittedText(): array
    {
        return $this->only(['crop_name', 'quality_description', 'harvest_date', 'quantity', 'unit_code',
            'price_per_unit', 'public_area', 'delivery_notes', 'delivery_options']);
    }

    public function messages(): array
    {
        $notJpgOrPng = __('Photo :position must be a JPG or PNG file. Export it as JPG or PNG and try again.');

        return [
            'harvest_date.before_or_equal' => __('Enter the actual harvest date. It cannot be in the future.'),
            'harvest_date.after_or_equal' => __('This harvest date looks wrong. Check the year.'),
            'harvest_date.date_format' => __('Choose a valid date.'),
            'quantity.regex' => __('Enter the quantity as a plain number, like 250 or 12.5 (at most 3 decimal places, no commas).'),
            'price_per_unit.regex' => __('Enter the price as a plain number, like 35 or 35.50 (at most 2 decimal places, no commas or ৳).'),
            'delivery_options.required' => __('Choose at least one delivery option.'),
            'photos.required' => __('Add at least one photo of the crop.'),
            'photos.max' => __('You can add at most :max photos.'),
            'photos.*.uploaded' => __('Photo :position did not finish uploading. It may be larger than 5 MB. Choose a smaller photo.'),
            'photos.*.file' => __('Photo :position did not finish uploading. It may be larger than 5 MB. Choose a smaller photo.'),
            'photos.*.max' => __('Photo :position is larger than 5 MB. Choose a smaller photo.'),
            'photos.*.mimes' => $notJpgOrPng,
            'photos.*.mimetypes' => $notJpgOrPng,
            'photos.*.dimensions' => __('Photo :position is too large in pixels. Use a photo of at most 6000 pixels on each side.'),
            'submission_token.required' => __('This form has expired. Open “Add crop listing” again.'),
            'submission_token.uuid' => __('This form has expired. Open “Add crop listing” again.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'crop_name' => __('crop name'),
            'quality_description' => __('quality description'),
            'harvest_date' => __('harvest date'),
            'quantity' => __('quantity'),
            'unit_code' => __('unit'),
            'price_per_unit' => __('price per unit'),
            'public_area' => __('area'),
            'delivery_options' => __('delivery options'),
            'delivery_options.*' => __('delivery option'),
            'delivery_notes' => __('delivery notes'),
            'photos' => __('photos'),
        ];
    }
}
