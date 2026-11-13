{{--
    "My cattle" (route cattle.index, Farmer role). F6. Controller: CattleController@index.
    Search and filters are plain GET forms and links, so they work without JavaScript and stay in the page links.
--}}
@php
    $scopes = ['active' => __('Active'), 'archived' => __('Archived'), 'all' => __('All')];
@endphp
<x-layouts.app :title="__('My cattle')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/cattle.css').'?v='.@filemtime(public_path('css/cattle.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('My cattle')" :subtitle="__('Your private herd records: photo, KB ID, age, parents and health history.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions><x-ui.button size="md" icon="plus" :href="route('cattle.create')">{{ __('Register cattle') }}</x-ui.button></x-slot:actions>
        </x-ui.page-header>

        @if ($badFilter)
            <x-ui.alert tone="info" class="mb-6">{{ __('That search or filter could not be used, so your active cattle are shown.') }}</x-ui.alert>
        @endif

        <div class="cattle-toolbar mb-6">
            <nav class="cluster" aria-label="{{ __('Filter cattle') }}">
                @foreach ($scopes as $key => $label)
                    <x-ui.chip :href="route('cattle.index', array_filter(['scope' => $key === 'active' ? null : $key, 'q' => $q ?: null]))" :selected="$scope === $key">
                        {{ $label }}@if ($key !== 'all') <span class="t-num">({{ $counts[$key] }})</span>@endif
                    </x-ui.chip>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('cattle.index') }}" class="cattle-search" role="search" aria-label="{{ __('Search my cattle') }}">
                @if ($scope !== 'active')<input type="hidden" name="scope" value="{{ $scope }}">@endif
                <label class="visually-hidden" for="cattle-search">{{ __('Search by KB ID, farm tag or nickname') }}</label>
                <div class="input-group">
                    <span class="input-group__addon"><x-ui.icon name="search" size="18" /></span>
                    <input id="cattle-search" name="q" type="search" class="input" value="{{ $q }}" maxlength="80" placeholder="{{ __('KB ID, farm tag or nickname') }}">
                </div>
                <x-ui.button type="submit" size="md" variant="secondary">{{ __('Search') }}</x-ui.button>
            </form>
        </div>

        @if ($cattle->isEmpty())
            @if ($q !== '')
                <x-ui.empty-state icon="search" :title="__('No cattle match your search')">
                    <x-ui.button size="md" variant="secondary" :href="route('cattle.index', $scope === 'active' ? [] : ['scope' => $scope])">{{ __('Clear search') }}</x-ui.button>
                </x-ui.empty-state>
            @elseif ($scope === 'archived')
                <x-ui.empty-state icon="cow-head" :title="__('No archived cattle')" :description="__('Animals you archive appear here, with their records kept.')" />
            @else
                <x-ui.empty-state icon="cow-head" :title="__('You have not registered any cattle yet')" :description="__('Add your first animal with a photo, so you can keep its age, parents and health history in one place.')">
                    <x-ui.button size="md" icon="plus" :href="route('cattle.create')">{{ __('Register cattle') }}</x-ui.button>
                </x-ui.empty-state>
            @endif
        @else
            <ul class="grid cattle-grid">
                @foreach ($cattle as $animal)
                    <li>
                        <article class="media-card cattle-card">
                            <div class="media-card__media">
                                <img src="{{ $animal->photoUrl('thumb') }}" alt="" width="{{ $animal->thumbnail_width }}" height="{{ $animal->thumbnail_height }}" loading="lazy" decoding="async">
                                @if ($animal->isArchived())<x-ui.badge tone="warning">{{ __('Archived') }}</x-ui.badge>@endif
                            </div>
                            <div class="media-card__body">
                                <h2 class="t-title-3 cattle-text"><a href="{{ route('cattle.show', $animal) }}" class="cattle-card__link">{{ $animal->title() }}</a></h2>
                                <p class="t-caption cattle-id">{{ $animal->displayId() }}</p>
                                <div class="media-card__meta">
                                    @if ($animal->farm_tag && $animal->nickname)<span class="cattle-text"><x-ui.icon name="flag" size="14" /> {{ $animal->farm_tag }}</span>@endif
                                    <span>{{ $animal->sexLabel() }}</span>
                                    <span class="cattle-text">{{ $animal->breed_description ?? __('Breed not recorded') }}</span>
                                    <span>{{ $animal->ageLabel() }}</span>
                                </div>
                                @unless ($animal->isArchived())
                                    <div class="media-card__footer">
                                        <x-ui.button size="sm" variant="tertiary" icon="pencil" :href="route('cattle.edit', $animal)">{{ __('Edit') }}<span class="visually-hidden"> {{ $animal->title() }}</span></x-ui.button>
                                    </div>
                                @endunless
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
            {{ $cattle->links('partials.pagination') }}
        @endif
    </div>
</x-layouts.app>
