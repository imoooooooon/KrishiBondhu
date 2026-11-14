<?php

namespace App\Http\Requests\SoilGuidance;

use App\Models\SoilGuidanceRecord;
use App\Services\CropListingPhotoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "Get soil guidance" form (route guidance.soil.store). F15.
 *
 * The farmer describes what they SEE. No laboratory values (pH, nitrogen, phosphorus, potassium) are asked.
 * "Not sure" is a normal, valid choice for colour, moisture and texture.
 * The photo is optional and follows the project's photo rule (JPG or PNG, 5 MB, same as F1 and F6).
 * The file's real content is checked (mimetypes + dimensions), not only its name.
 */
class StoreSoilGuidanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SoilGuidanceRecord::class);
    }

    public function rules(): array
    {
        $side = CropListingPhotoService::MAX_SIDE;

        return [
            'submission_token' => ['required', 'uuid'],
            'crop_name' => ['required', 'string', 'min:2', 'max:100'],
            'soil_color' => ['required', Rule::in(array_keys(SoilGuidanceRecord::SOIL_COLORS))],
            'moisture' => ['required', Rule::in(array_keys(SoilGuidanceRecord::MOISTURES))],
            'texture' => ['required', Rule::in(array_keys(SoilGuidanceRecord::TEXTURES))],
            'observations' => ['nullable', 'string', 'max:2000'],
            'photo' => [
                'nullable', 'bail', 'file', 'max:'.CropListingPhotoService::MAX_KILOBYTES,
                'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png',
                "dimensions:max_width={$side},max_height={$side}",
            ],
        ];
    }

    /** One more photo check after the rules: the total number of pixels (a very large photo uses too much memory). */
    public function after(): array
    {
        return [function (Validator $validator) {
            $file = $this->file('photo');
            $size = $file instanceof UploadedFile && $file->isValid() ? @getimagesize($file->getRealPath()) : false;
            if ($size && $size[0] * $size[1] > CropListingPhotoService::MAX_PIXELS) {
                $validator->errors()->add('photo', __('This photo is too large in pixels. Use a photo of at most 6000 pixels on each side and 12 megapixels.'));
            }
        }];
    }

    /**
     * The observations to save. Only keys from rules(): the owner, status and AI answer can never be sent in.
     *
     * @return array<string, mixed>
     */
    public function observationFields(): array
    {
        return $this->safe()->only(['crop_name', 'soil_color', 'moisture', 'texture', 'observations']) + ['observations' => null];
    }

    public function messages(): array
    {
        return [
            'crop_name.required' => __('Write the name of the crop you grow.'),
            'soil_color.required' => __('Choose the soil colour. Choose “Not sure” if you cannot tell.'),
            'soil_color.in' => __('Choose the soil colour. Choose “Not sure” if you cannot tell.'),
            'moisture.required' => __('Choose how wet the soil is. Choose “Not sure” if you cannot tell.'),
            'moisture.in' => __('Choose how wet the soil is. Choose “Not sure” if you cannot tell.'),
            'texture.required' => __('Choose how the soil feels. Choose “Not sure” if you cannot tell.'),
            'texture.in' => __('Choose how the soil feels. Choose “Not sure” if you cannot tell.'),
            'observations.max' => __('Your notes are too long. Use 2,000 characters or fewer.'),
            'photo.uploaded' => __('The photo did not finish uploading, probably because it is too large for the server. Choose a smaller photo (5 MB or less).'),
            'photo.file' => __('The photo could not be read. Choose it again.'),
            'photo.max' => __('The photo is larger than 5 MB. Choose a smaller photo.'),
            'photo.mimes' => __('The photo must be a JPG or PNG file.'),
            'photo.mimetypes' => __('The photo must be a JPG or PNG file.'),
            'photo.dimensions' => __('The photo is too large in pixels. Use a photo of at most 6000 pixels on each side.'),
            'submission_token.required' => __('This form has expired. Open “Get soil guidance” again.'),
            'submission_token.uuid' => __('This form has expired. Open “Get soil guidance” again.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'crop_name' => __('crop name'), 'soil_color' => __('soil colour'), 'moisture' => __('moisture'),
            'texture' => __('texture'), 'observations' => __('notes'), 'photo' => __('photo'),
        ];
    }
}
