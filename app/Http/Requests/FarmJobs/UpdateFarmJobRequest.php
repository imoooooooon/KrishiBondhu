<?php

namespace App\Http\Requests\FarmJobs;

use App\Models\FarmJob;

/** "Edit job" form (route farmer.jobs.update). Same fields and rules as posting, with two differences below. */
class UpdateFarmJobRequest extends StoreFarmJobRequest
{
    /** Only the owner may edit (FarmJobPolicy::update). */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->job());
    }

    /**
     * An old job may keep its original (past) date while the farmer corrects other details.
     * Changing the date to any other past day is rejected, like on a new job.
     */
    protected function workDateRules(): array
    {
        $unchanged = $this->input('work_date') === $this->job()->work_date->toDateString();

        return $unchanged ? ['required', 'date_format:Y-m-d'] : parent::workDateRules();
    }

    /** The job from the URL (/farmer/jobs/{job}). */
    private function job(): FarmJob
    {
        return $this->route('job');
    }
}
