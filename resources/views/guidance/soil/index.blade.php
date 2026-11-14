{{--
    "My soil guidance" (route guidance.soil.index, Farmer role). F15. Controller: SoilGuidanceController@index.
    Only the signed-in farmer's own requests, newest first, 10 per page.
--}}
<x-layouts.app :title="__('My soil guidance')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/soil-guidance.css').'?v='.@filemtime(public_path('css/soil-guidance.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('My soil guidance')" :subtitle="__('Your earlier soil guidance requests and their results. Only you can see them.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions><x-ui.button size="md" icon="plus" :href="route('guidance.soil.create')">{{ __('New request') }}</x-ui.button></x-slot:actions>
        </x-ui.page-header>

        @if ($records->isEmpty())
            <x-ui.empty-state icon="sprout" :title="__('You have not asked for soil guidance yet')"
                :description="__('Describe your crop and soil to get general soil-care and fertilizer guidance. A photo is optional.')">
                <x-ui.button size="md" icon="plus" :href="route('guidance.soil.create')">{{ __('Get soil guidance') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <ul class="soil-records">
                @foreach ($records as $record)
                    <li>
                        <x-ui.card class="soil-record">
                            <div class="stack stack-sm">
                                <h2 class="t-title-3 soil-text">{{ $record->crop_name }}</h2>
                                <p class="cluster">
                                    <x-ui.badge :tone="$record->statusTone()">{{ $record->statusLabel() }}</x-ui.badge>
                                    <span class="t-callout t-muted"><time datetime="{{ $record->created_at->toIso8601String() }}">{{ $record->requestedAtForDisplay() }}</time></span>
                                </p>
                            </div>
                            <x-ui.button size="md" variant="secondary" icon-after="chevron-right" :href="route('guidance.soil.show', $record)">
                                {{ __('View result') }}<span class="visually-hidden"> {{ $record->crop_name }}, {{ $record->requestedAtForDisplay() }}</span>
                            </x-ui.button>
                        </x-ui.card>
                    </li>
                @endforeach
            </ul>

            {{ $records->links('partials.pagination') }}
        @endif
    </div>
</x-layouts.app>
