{{--
    Browse and search farm jobs (route: jobs.index, public). F20. Controller: FarmJobController@index.
    The filters are sent with GET, so they stay in the address bar and in the page links.
--}}
@php
    $any = ['' => __('Any')];
@endphp
<x-layouts.app :title="__('Farm jobs')" :description="__('Search farm work near you by location, date, work type and wage.')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/farm-jobs.css') }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('Find farm work')" :subtitle="__('Open jobs posted by farmers. Search by place, date, work type and wage.')">
            @can('create', \App\Models\FarmJob::class)
                <x-slot:actions>
                    <x-ui.button size="md" variant="secondary" icon="list" :href="route('farmer.jobs.index')">{{ __('My posted jobs') }}</x-ui.button>
                    <x-ui.button size="md" icon="plus" :href="route('farmer.jobs.create')">{{ __('Post a job') }}</x-ui.button>
                </x-slot:actions>
            @endcan
        </x-ui.page-header>

        <x-ui.card class="mb-8">
            <form method="GET" action="{{ route('jobs.index') }}" class="stack" role="search" aria-label="{{ __('Search jobs') }}" novalidate>
                <div class="grid job-filters">
                    <x-ui.field name="q" type="search" :label="__('Keyword')" :value="$filters['q'] ?? null" :hint="__('Searches the job title and description')" maxlength="100" />
                    <x-ui.field name="location" :label="__('Location')" :value="$filters['location'] ?? null" :hint="__('Village, upazila or district')" maxlength="100" />
                    <x-ui.select name="work_type" :label="__('Work type')" :options="$any + \App\Models\FarmJob::workTypeLabels()" :value="$filters['work_type'] ?? null" />
                    <x-ui.field name="work_date" type="date" :label="__('Work date')" :value="$filters['work_date'] ?? null" />
                    <x-ui.select name="wage_basis" :label="__('Wage basis')" :options="$any + \App\Models\FarmJob::wageBasisLabels()" :value="$filters['wage_basis'] ?? null" />
                    <x-ui.field name="min_wage" type="number" :label="__('Minimum wage')" :value="$filters['min_wage'] ?? null" prefix="৳"
                        :hint="__('Choose a wage basis first')" min="0" step="1" inputmode="decimal" />
                </div>
                <div class="cluster">
                    <x-ui.button type="submit" size="md" icon="search">{{ __('Search') }}</x-ui.button>
                    <x-ui.button size="md" variant="tertiary" :href="route('jobs.index')">{{ __('Clear filters') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($jobs->isEmpty())
            @if ($filters)
                <x-ui.empty-state icon="search" :title="__('No jobs match your search')" :description="__('Try fewer filters, another location or another date.')">
                    <x-ui.button size="md" variant="secondary" :href="route('jobs.index')">{{ __('Clear filters') }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state icon="tractor" :title="__('No open jobs right now')" :description="__('Farmers have not posted any work for the coming days. Please check again soon.')" />
            @endif
        @else
            <p class="t-callout t-muted mb-4" role="status">{{ trans_choice(':count open job|:count open jobs', $jobs->total(), ['count' => $jobs->total()]) }}</p>

            <div class="grid-2">
                @foreach ($jobs as $job)
                    @include('farm-jobs._card', ['job' => $job])
                @endforeach
            </div>

            {{ $jobs->links('partials.pagination') }}
        @endif
    </div>
</x-layouts.app>
