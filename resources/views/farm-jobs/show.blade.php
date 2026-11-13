{{--
    Job details (routes: jobs.show public, farmer.jobs.show). F20. Controller: FarmJobController@show.
    Only the farmer's name is shown, not the phone number. Applying for the job is F21 and is not built yet.
--}}
@php
    $status = $job->displayStatus();
    $notSpecified = __('Not specified');
@endphp
<x-layouts.app :title="$job->title" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/farm-jobs.css') }}"></x-slot:head>

    <div>
        <x-ui.page-header :title="$job->title" class="job-text">
            <x-slot:breadcrumbs>
                <a href="{{ route('jobs.index') }}">{{ __('Farm jobs') }}</a>
                @can('update', $job)
                    <x-ui.icon name="chevron-right" size="14" />
                    <a href="{{ route('farmer.jobs.index') }}">{{ __('My posted jobs') }}</a>
                @endcan
            </x-slot:breadcrumbs>
        </x-ui.page-header>

        <div class="stack">
            <div class="cluster">
                <x-ui.badge tone="harvest">{{ $job->workTypeLabel() }}</x-ui.badge>
                @include('farm-jobs._status', ['status' => $status])
            </div>

            @if ($status === 'closed')
                <x-ui.alert tone="neutral" :title="__('This job is closed')">{{ __('Workers cannot see it any more. Only you can open this page.') }}</x-ui.alert>
            @elseif ($status === 'past')
                <x-ui.alert tone="warning" :title="__('The work date has passed')">{{ __('Workers cannot see this job any more. Only you can open this page.') }}</x-ui.alert>
            @endif

            <x-ui.card>
                <dl class="job-facts">
                    <div><dt>{{ __('Location') }}</dt><dd>{{ $job->location }}</dd></div>
                    <div><dt>{{ __('Work date') }}</dt><dd>{{ $job->work_date->translatedFormat('l, j F Y') }}</dd></div>
                    <div><dt>{{ __('Workers needed') }}</dt><dd class="t-num">{{ $job->required_workers }}</dd></div>
                    <div>
                        <dt>{{ __('Proposed wage') }}</dt>
                        <dd><x-ui.price :amount="$job->proposed_wage" /> {{ $job->wageLabel() }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('Meals') }}</dt>
                        <dd @class(['t-subtle' => ! $job->meal_details])>{{ $job->meal_details ?? $notSpecified }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('Transport') }}</dt>
                        <dd @class(['t-subtle' => ! $job->transport_details])>{{ $job->transport_details ?? $notSpecified }}</dd>
                    </div>
                    <div><dt>{{ __('Posted by') }}</dt><dd>{{ $job->farmer->name }}</dd></div>
                    <div><dt>{{ __('Posted on') }}</dt><dd>{{ $job->created_at->timezone(\App\Models\FarmJob::TIMEZONE)->translatedFormat('j F Y') }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <x-slot:title>{{ __('About the work') }}</x-slot:title>
                <p class="job-text job-text--lines">{{ $job->description }}</p>
            </x-ui.card>

            @can('update', $job)
                <div class="cluster">
                    <x-ui.button size="md" variant="secondary" icon="pencil" :href="route('farmer.jobs.edit', $job)">{{ __('Edit') }}</x-ui.button>
                    @if ($status === 'open')
                        @include('farm-jobs._close-form', ['job' => $job])
                    @endif
                </div>
            @endcan
        </div>
    </div>

    <x-slot:scripts><script src="{{ asset('js/farm-jobs.js') }}" defer></script></x-slot:scripts>
</x-layouts.app>
