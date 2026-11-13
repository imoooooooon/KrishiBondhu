<?php

namespace App\Http\Controllers;

use App\Http\Requests\FarmJobs\SearchFarmJobsRequest;
use App\Http\Requests\FarmJobs\StoreFarmJobRequest;
use App\Http\Requests\FarmJobs\UpdateFarmJobRequest;
use App\Models\FarmJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * F20: farm job posting and search. Routes: routes/modules/jobs.php (IA.md §5.7).
 * Public: index, show. Farmer (signed in): myJobs, create, store, edit, update, close.
 * Rules live elsewhere: who may act -> FarmJobPolicy, valid input -> Form Requests, queries -> FarmJob scopes.
 */
class FarmJobController extends Controller
{
    /** Browse and search available jobs (jobs.index). */
    public function index(SearchFarmJobsRequest $request): View
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null);

        $jobs = FarmJob::query()
            ->available()
            ->search($filters)
            ->orderBy('work_date')   // soonest work first
            ->orderBy('id')          // same date: always the same order
            ->paginate(10)
            ->withQueryString();     // page links keep the filters

        return view('farm-jobs.index', ['jobs' => $jobs, 'filters' => $filters]);
    }

    /** Job details (jobs.show, farmer.jobs.show). Closed and past jobs are visible to the owner only. */
    public function show(FarmJob $job): View
    {
        Gate::authorize('view', $job);

        return view('farm-jobs.show', ['job' => $job->load('farmer')]);
    }

    /** The signed-in farmer's own jobs: open, closed and past (farmer.jobs.index). */
    public function myJobs(Request $request): View
    {
        Gate::authorize('create', FarmJob::class); // the farmer workspace needs the Farmer role

        $jobs = $request->user()->farmJobs()
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('farm-jobs.mine', ['jobs' => $jobs]);
    }

    /** The "Post a job" form (farmer.jobs.create). */
    public function create(): View
    {
        Gate::authorize('create', FarmJob::class);

        return view('farm-jobs.create');
    }

    /** Save a new job (farmer.jobs.store). The owner is always the signed-in user, never a form field. */
    public function store(StoreFarmJobRequest $request): RedirectResponse
    {
        $job = $request->user()->farmJobs()->create($request->jobData());

        return redirect()->route('farmer.jobs.show', $job)->with('status', __('Your job is posted. Workers can find it now.'));
    }

    /** The edit form (farmer.jobs.edit). */
    public function edit(FarmJob $job): View
    {
        Gate::authorize('update', $job);

        return view('farm-jobs.edit', ['job' => $job]);
    }

    /** Save changes (farmer.jobs.update). UpdateFarmJobRequest checks ownership and the input. */
    public function update(UpdateFarmJobRequest $request, FarmJob $job): RedirectResponse
    {
        $job->update($request->jobData());

        return redirect()->route('farmer.jobs.show', $job)->with('status', __('Job updated.'));
    }

    /** End recruitment (farmer.jobs.close). The record stays; closing twice changes nothing. */
    public function close(FarmJob $job): RedirectResponse
    {
        Gate::authorize('close', $job);

        if (! $job->isClosed()) {
            $job->status = FarmJob::STATUS_CLOSED;
            $job->save();
        }

        return redirect()->route('farmer.jobs.index')->with('status', __('Job closed. Workers can no longer see it.'));
    }
}
