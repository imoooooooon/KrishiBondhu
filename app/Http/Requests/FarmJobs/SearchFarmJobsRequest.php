<?php

namespace App\Http\Requests\FarmJobs;

use App\Models\FarmJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The search filters on the job list (route jobs.index). They arrive in the URL (GET),
 * e.g. /jobs?location=Savar&wage_basis=daily&min_wage=600, so a search can be refreshed or shared.
 * Every filter is optional. Anyone may search, so there is no authorize() rule.
 */
class SearchFarmJobsRequest extends FormRequest
{
    /** A wrong filter (typed into the URL) sends the visitor back to the plain job list with the error shown. */
    protected $redirectRoute = 'jobs.index';

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'work_type' => ['nullable', Rule::in(FarmJob::WORK_TYPES)],
            'work_date' => ['nullable', 'date_format:Y-m-d'],
            // ৳ 700 per day and ৳ 700 per bag cannot be compared, so a minimum wage needs a wage basis.
            'wage_basis' => ['nullable', 'required_with:min_wage', Rule::in(FarmJob::WAGE_BASES)],
            'min_wage' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'wage_basis.required_with' => __('Choose a wage basis to filter by minimum wage. A daily wage and a task wage cannot be compared.'),
            'work_date.date_format' => __('Choose a valid date.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => __('keyword'),
            'location' => __('location'),
            'work_type' => __('work type'),
            'work_date' => __('work date'),
            'wage_basis' => __('wage basis'),
            'min_wage' => __('minimum wage'),
        ];
    }
}
