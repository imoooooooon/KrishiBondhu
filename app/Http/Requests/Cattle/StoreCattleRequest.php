<?php

namespace App\Http\Requests\Cattle;

use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Services\CattleAge;
use App\Services\CropListingPhotoService;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "Register cattle" form (route cattle.store). UpdateCattleRequest reuses the profile and age rules.
 * Four sections: identity + photo, age, parents, optional first health note (spec §8.2, §9).
 */
class StoreCattleRequest extends FormRequest
{
    use NormalisesBanglaDigits;

    public function authorize(): bool|Response
    {
        return $this->user()->can('create', Cattle::class);
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseDigits(['birth_date', 'estimate_years', 'estimate_months', 'history_occurred_on']);
    }

    public function rules(): array
    {
        return $this->profileRules() + $this->ageRules() + [
            'photo' => ['required', ...$this->photoRules()],
            'submission_token' => ['required', 'uuid'],
            // Optional first health note: only saved when a title is given.
            'history_title' => ['nullable', 'string', 'min:3', 'max:120', 'required_with:history_details'],
            'history_details' => ['nullable', 'string', 'min:5', 'max:2000', 'required_with:history_title'],
            'history_type' => ['nullable', 'required_with:history_title', Rule::in(CattleHealthEntry::TYPES)],
            'history_date_known' => ['nullable', Rule::in(['yes', 'no'])],
            'history_occurred_on' => ['nullable', 'required_if:history_date_known,yes', 'date_format:Y-m-d', 'after_or_equal:1970-01-01', 'before_or_equal:'.CattleAge::today()],
            'history_source' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function profileRules(?int $ignoreId = null): array
    {
        return [
            'nickname' => ['nullable', 'string', 'max:80'],
            // Unique among THIS farmer's cattle, archived ones included. MySQL compares without case,
            // so "TAG-1" and "tag-1" count as the same tag, in this rule and in the database index.
            'farm_tag' => ['nullable', 'string', 'max:50', Rule::unique('cattle', 'farm_tag')->where('farmer_id', $this->user()->id)->ignore($ignoreId)],
            'sex' => ['required', Rule::in(Cattle::SEXES)],
            'breed_known' => ['required', Rule::in(['yes', 'no'])],
            'breed_description' => ['nullable', 'required_if:breed_known,yes', 'string', 'min:2', 'max:100'],
            'mother_details' => ['nullable', 'string', 'max:300'],
            'father_details' => ['nullable', 'string', 'max:300'],
        ];
    }

    protected function ageRules(): array
    {
        return [
            'age_basis' => ['required', Rule::in(Cattle::AGE_BASES)],
            'birth_date' => ['nullable', 'required_if:age_basis,exact', 'date_format:Y-m-d', 'after_or_equal:1970-01-01', 'before_or_equal:'.CattleAge::today()],
            'estimate_years' => ['nullable', 'required_if:age_basis,estimated', 'integer', 'min:0', 'max:50'],
            'estimate_months' => ['nullable', 'integer', 'min:0', 'max:11'],
        ];
    }

    protected function photoRules(): array
    {
        $side = CropListingPhotoService::MAX_SIDE;

        return ['bail', 'file', 'max:'.CropListingPhotoService::MAX_KILOBYTES, 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', "dimensions:max_width={$side},max_height={$side}"];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $this->checkAgeTotal($validator);
            $this->checkPixels($validator);

            // The first health note cannot be dated before a known birth date.
            $date = $this->input('history_occurred_on');
            if ($this->input('age_basis') === 'exact' && $this->input('history_date_known') === 'yes'
                && is_string($date) && is_string($this->input('birth_date')) && $date < $this->input('birth_date')) {
                $validator->errors()->add('history_occurred_on', __('This date is before the birth date you entered.'));
            }
        }];
    }

    protected function checkAgeTotal(Validator $validator): void
    {
        if ($this->input('age_basis') === 'estimated' && $this->estimatedMonths() > 600) {
            $validator->errors()->add('estimate_years', __('An estimated age can be at most 50 years.'));
        }
    }

    protected function checkPixels(Validator $validator): void
    {
        $file = $this->file('photo');
        $size = $file instanceof UploadedFile && $file->isValid() ? @getimagesize($file->getRealPath()) : false;
        if ($size && $size[0] * $size[1] > CropListingPhotoService::MAX_PIXELS) {
            $validator->errors()->add('photo', __('This photo is too large in pixels. Use a photo of at most 6000 pixels on each side and 12 megapixels.'));
        }
    }

    private function estimatedMonths(): int
    {
        return (int) $this->input('estimate_years') * 12 + (int) $this->input('estimate_months');
    }

    /** Profile fields to save. Only keys from rules(): farmer_id, public_id or archived_at can never be sent in. */
    public function profileData(): array
    {
        $data = $this->safe()->only(['nickname', 'farm_tag', 'sex', 'breed_description', 'mother_details', 'father_details']);
        $data += ['nickname' => null, 'farm_tag' => null, 'breed_description' => null, 'mother_details' => null, 'father_details' => null];
        if ($this->validated('breed_known') === 'no') {
            $data['breed_description'] = null; // "unknown" really means unknown
        }

        return $data;
    }

    /** @return array{basis: string, birth_date: ?string, months: ?int} only the chosen mode's value is kept */
    public function ageData(): array
    {
        $basis = $this->validated('age_basis');

        return [
            'basis' => $basis,
            'birth_date' => $basis === 'exact' ? $this->validated('birth_date') : null,
            'months' => $basis === 'estimated' ? $this->estimatedMonths() : null,
        ];
    }

    /** The optional first health note, or null. */
    public function firstEntry(): ?array
    {
        if (! filled($this->validated('history_title'))) {
            return null;
        }

        return [
            'entry_type' => $this->validated('history_type'),
            'title' => $this->validated('history_title'),
            'details' => $this->validated('history_details'),
            'occurred_on' => $this->validated('history_date_known') === 'yes' ? $this->validated('history_occurred_on') : null,
            'source_label' => $this->validated('history_source'),
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => __('The birth date cannot be in the future.'),
            'history_occurred_on.before_or_equal' => __('Health history is about the past: the date cannot be in the future.'),
            'birth_date.required_if' => __('Enter the birth date, or choose “Estimated age” or “Not known”.'),
            'estimate_years.required_if' => __('Enter the estimated age in years (0 for a young calf).'),
            'breed_description.required_if' => __('Describe the breed, or choose “Not known”.'),
            'photo.required' => __('Add one photo of the animal.'),
            'photo.uploaded' => __('The photo did not finish uploading, probably because it is too large for the server. Choose a smaller photo (5 MB or less).'),
            'photo.max' => __('The photo is larger than 5 MB. Choose a smaller photo.'),
            'photo.mimes' => __('The photo must be a JPG or PNG file.'),
            'photo.mimetypes' => __('The photo must be a JPG or PNG file.'),
            'photo.dimensions' => __('The photo is too large in pixels. Use a photo of at most 6000 pixels on each side.'),
            'farm_tag.unique' => __('You already use this farm tag for another animal (also check Archived).'),
            'submission_token.required' => __('This form has expired. Open “Register cattle” again.'),
            'submission_token.uuid' => __('This form has expired. Open “Register cattle” again.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'nickname' => __('nickname'), 'farm_tag' => __('farm tag'), 'sex' => __('sex'),
            'breed_known' => __('breed'), 'breed_description' => __('breed'),
            'mother_details' => __('mother details'), 'father_details' => __('father details'),
            'age_basis' => __('age'), 'birth_date' => __('birth date'),
            'estimate_years' => __('years'), 'estimate_months' => __('months'), 'photo' => __('photo'),
            'history_title' => __('title'), 'history_details' => __('details'), 'history_type' => __('type'),
            'history_occurred_on' => __('date'), 'history_source' => __('source'),
        ];
    }
}
