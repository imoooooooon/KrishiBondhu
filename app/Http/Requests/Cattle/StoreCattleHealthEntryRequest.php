<?php

namespace App\Http\Requests\Cattle;

use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Services\CattleAge;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** "Add health entry" (route cattle.health-history.store). A note about the past, written by the owner. */
class StoreCattleHealthEntryRequest extends FormRequest
{
    use NormalisesBanglaDigits;

    public function authorize(): bool|Response
    {
        return Gate::inspect('update', $this->cattle());
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseDigits(['occurred_on']);
    }

    public function rules(): array
    {
        return $this->entryRules() + ['submission_token' => ['required', 'uuid']];
    }

    protected function entryRules(): array
    {
        return [
            'entry_type' => ['required', Rule::in(CattleHealthEntry::TYPES)],
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'date_known' => ['required', Rule::in(['yes', 'no'])],
            'occurred_on' => ['nullable', 'required_if:date_known,yes', 'date_format:Y-m-d', 'after_or_equal:1970-01-01', 'before_or_equal:'.CattleAge::today()],
            'details' => ['required', 'string', 'min:5', 'max:2000'],
            'source_label' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** Fields to save. An unknown date is stored as null, never as "today". */
    public function entryData(): array
    {
        $data = $this->safe()->only(['entry_type', 'title', 'details', 'source_label']) + ['source_label' => null];
        $data['occurred_on'] = $this->validated('date_known') === 'yes' ? $this->validated('occurred_on') : null;

        return $data;
    }

    public function submittedText(): array
    {
        return $this->only(['title', 'occurred_on', 'details', 'source_label']);
    }

    public function messages(): array
    {
        return [
            'occurred_on.before_or_equal' => __('Health history is about the past: the date cannot be in the future.'),
            'occurred_on.required_if' => __('Enter the date, or choose “Date not known”.'),
            'submission_token.required' => __('This form has expired. Open “Add health entry” again.'),
            'submission_token.uuid' => __('This form has expired. Open “Add health entry” again.'),
        ];
    }

    public function attributes(): array
    {
        return ['entry_type' => __('type'), 'title' => __('title'), 'occurred_on' => __('date'), 'details' => __('details'), 'source_label' => __('source')];
    }

    public function cattle(): Cattle
    {
        return $this->route('cattle');
    }
}
