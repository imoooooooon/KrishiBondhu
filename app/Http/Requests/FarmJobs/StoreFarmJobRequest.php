<?php

namespace App\Http\Requests\FarmJobs;

use App\Models\FarmJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** "Post a job" form (route farmer.jobs.store). UpdateFarmJobRequest reuses these rules. */
class StoreFarmJobRequest extends FormRequest
{
    /** Only farmers may post (FarmJobPolicy::create). */
    public function authorize(): bool
    {
        return $this->user()->can('create', FarmJob::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'work_type' => ['required', Rule::in(FarmJob::WORK_TYPES)],
            'location' => ['required', 'string', 'max:255'],
            'work_date' => $this->workDateRules(),
            'required_workers' => ['required', 'integer', 'min:1', 'max:1000'],
            // decimal(10, 2) in the database: at most 8 digits before the point and 2 after.
            'proposed_wage' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'wage_basis' => ['required', Rule::in(FarmJob::WAGE_BASES)],
            'unit_label' => ['nullable', 'required_if:wage_basis,unit', 'string', 'max:50'],
            'meal_details' => ['nullable', 'string', 'max:1000'],
            'transport_details' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** A new job cannot be for a day that has already passed (Bangladesh date). */
    protected function workDateRules(): array
    {
        return ['required', 'date_format:Y-m-d', 'after_or_equal:'.FarmJob::today()];
    }

    /**
     * The checked values, ready to save. Only the fields listed in rules() are returned,
     * so extra fields such as farmer_id or status are dropped. The unit label is kept only for unit wages.
     *
     * @return array<string, mixed>
     */
    public function jobData(): array
    {
        $data = $this->validated();

        if ($data['wage_basis'] !== 'unit') {
            $data['unit_label'] = null;
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'work_date.after_or_equal' => __('The work date cannot be in the past.'),
            'work_date.date_format' => __('Choose a valid date.'),
            'unit_label.required_if' => __('Say what is counted for a unit wage, for example bag, maund or bigha.'),
            'proposed_wage.gt' => __('The wage must be more than zero.'),
            'proposed_wage.decimal' => __('Enter the wage in taka, with at most two decimal places.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('job title'),
            'description' => __('description'),
            'work_type' => __('work type'),
            'location' => __('location'),
            'work_date' => __('work date'),
            'required_workers' => __('number of workers'),
            'proposed_wage' => __('proposed wage'),
            'wage_basis' => __('wage basis'),
            'unit_label' => __('unit'),
            'meal_details' => __('meal details'),
            'transport_details' => __('transport details'),
        ];
    }
}
