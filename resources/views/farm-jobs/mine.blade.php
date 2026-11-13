{{--
    My posted jobs (route: farmer.jobs.index, farmer only). F20. Controller: FarmJobController@myJobs.
    Shows the farmer's open, closed and past jobs. Closed and past jobs stay here as history.
--}}
<x-layouts.app :title="__('My posted jobs')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/farm-jobs.css') }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('My posted jobs')" :subtitle="__('Jobs you posted. Workers only see the open ones whose date has not passed.')">
            <x-slot:breadcrumbs><a href="{{ route('jobs.index') }}">{{ __('Farm jobs') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" icon="plus" :href="route('farmer.jobs.create')">{{ __('Post a job') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($jobs->isEmpty())
            <x-ui.empty-state icon="tractor" :title="__('You have not posted a job yet')" :description="__('Post your first job so workers can find it.')">
                <x-ui.button size="md" icon="plus" :href="route('farmer.jobs.create')">{{ __('Post a job') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="grid-2">
                @foreach ($jobs as $job)
                    @include('farm-jobs._card', ['job' => $job, 'manage' => true])
                @endforeach
            </div>

            {{ $jobs->links('partials.pagination') }}
        @endif
    </div>

    <x-slot:scripts><script src="{{ asset('js/farm-jobs.js') }}" defer></script></x-slot:scripts>
</x-layouts.app>
