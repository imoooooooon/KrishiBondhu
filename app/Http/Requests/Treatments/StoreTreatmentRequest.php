<?php

namespace App\Http\Requests\Treatments;

use App\Models\Role;
use App\Models\TreatmentRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::FARMER) ?? false;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(TreatmentRecord::CATEGORIES)],
            'subject_name' => ['required', 'string', 'max:120'],
            'problem' => ['required', 'string', 'max:2000'],
            'treatment_notes' => ['required', 'string', 'max:2000'],
            'treated_on' => ['required', 'date'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:treated_on'],
            'status' => ['required', 'string', Rule::in(TreatmentRecord::STATUSES)],
        ];
    }
}
