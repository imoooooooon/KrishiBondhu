{{--
    One job in a list (F20). Used by the public job list and by "My posted jobs".
    $job: the FarmJob. $manage: true on the farmer's own list (adds the status badge, Edit and Close).
--}}
@php
    $manage = $manage ?? false;
    $status = $job->displayStatus();
@endphp
<x-ui.card class="stack">
    <div class="stack stack-sm">
        <h2 class="t-title-3 job-text">{{ $job->title }}</h2>
        <div class="cluster">
            <x-ui.badge tone="harvest">{{ $job->workTypeLabel() }}</x-ui.badge>
            @if ($manage)
                @include('farm-jobs._status', ['status' => $status])
            @endif
        </div>
    </div>

    <dl class="job-facts">
        <div><dt>{{ __('Location') }}</dt><dd>{{ $job->location }}</dd></div>
        <div><dt>{{ __('Work date') }}</dt><dd>{{ $job->work_date->translatedFormat('D, j M Y') }}</dd></div>
        <div><dt>{{ __('Workers needed') }}</dt><dd class="t-num">{{ $job->required_workers }}</dd></div>
        <div><dt>{{ __('Proposed wage') }}</dt><dd><x-ui.price :amount="$job->proposed_wage" /> {{ $job->wageLabel() }}</dd></div>
        @if ($job->meal_details)
            <div><dt>{{ __('Meals') }}</dt><dd>{{ $job->meal_details }}</dd></div>
        @endif
    </dl>

    <div class="cluster">
        <x-ui.button size="md" variant="secondary" icon="eye" :href="route($manage ? 'farmer.jobs.show' : 'jobs.show', $job)">{{ __('View details') }}</x-ui.button>
        @if ($manage)
            <x-ui.button size="md" variant="tertiary" icon="pencil" :href="route('farmer.jobs.edit', $job)">{{ __('Edit') }}</x-ui.button>
            @if ($status === 'open')
                @include('farm-jobs._close-form', ['job' => $job])
            @endif
        @endif
    </div>
</x-ui.card>
