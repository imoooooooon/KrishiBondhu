<?php

namespace App\Http\Requests\Treatments;

use App\Models\TreatmentRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('treatment');

        return $record instanceof TreatmentRecord
            && ($this->user()?->can('update', $record) ?? false);
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