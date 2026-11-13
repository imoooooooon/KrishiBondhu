<?php

namespace App\Http\Requests\Cattle;

use App\Models\Cattle;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Route cattle.update (PATCH). Two operations, chosen by the hidden "operation" field:
 *  - save:    the edit form. age_update=keep leaves the age exactly as it is; replace sets a new one.
 *  - restore: bring an archived animal back; accepts nothing else, so it can never change other fields.
 */
class UpdateCattleRequest extends StoreCattleRequest
{
    /** Owner only: non-farmer 403, another farmer 404. Runs before validation and before any photo work. */
    public function authorize(): bool|Response
    {
        return Gate::inspect('update', $this->cattle());
    }

    public function rules(): array
    {
        $rules = [
            'operation' => ['required', Rule::in(['save', 'restore'])],
            'version' => ['required', 'integer', 'min:1'],
        ];
        if ($this->input('operation') !== 'save') {
            return $rules;
        }

        $rules += $this->profileRules($this->cattle()->id) + [
            'age_update' => ['required', Rule::in(['keep', 'replace'])],
            'photo' => ['nullable', ...$this->photoRules()],
        ];

        return $this->input('age_update') === 'replace' ? $rules + $this->ageRules() : $rules;
    }

    public function after(): array
    {
        if ($this->input('operation') !== 'save') {
            return [];
        }

        return [function (Validator $validator) {
            if ($this->input('age_update') === 'replace') {
                $this->checkAgeTotal($validator);
            }
            $this->checkPixels($validator);
        }];
    }

    /** The new age, or null for "keep current age information" (then nothing about the age changes). */
    public function ageChange(): ?array
    {
        return $this->validated('age_update') === 'replace' ? $this->ageData() : null;
    }

    public function operation(): string
    {
        return $this->validated('operation');
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    /** Text the farmer typed, shown back on the 409 page. */
    public function submittedText(): array
    {
        return $this->only(['nickname', 'farm_tag', 'breed_description', 'mother_details', 'father_details', 'birth_date']);
    }

    public function cattle(): Cattle
    {
        return $this->route('cattle');
    }
}
