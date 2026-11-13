{{--
    "My crop listings" (route farmer.listings.index, Farmer role). F1. Controller: Farmer\CropListingController@index.
    Filters are plain links (?status=published), so they work without JavaScript and stay in the page links.
    Each card: the title links to the listing; "Edit" is a separate link (no button inside a link).
--}}
@php
    $filters = [
        'all' => __('All'),
        'published' => __('Published'),
        'unpublished' => __('Hidden'),
    ];
@endphp
<x-layouts.app :title="__('My crop listings')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/crop-listings.css').'?v='.@filemtime(public_path('css/crop-listings.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('My crop listings')" :subtitle="__('Crops you offer on the marketplace. Keep them up to date.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="store" :href="route('marketplace.index')">{{ __('View marketplace') }}</x-ui.button>
                <x-ui.button size="md" icon="plus" :href="route('farmer.listings.create')">{{ __('Add crop listing') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('listing_warning'))
            <x-ui.alert tone="warning" class="mb-6">{{ session('listing_warning') }}</x-ui.alert>
        @endif
        @if ($unknownFilter)
            <x-ui.alert tone="info" class="mb-6">{{ __('That filter does not exist, so all your listings are shown.') }}</x-ui.alert>
        @endif

        @if ($counts['all'] > 0)
            <nav class="cluster mb-6" aria-label="{{ __('Filter listings') }}">
                @foreach ($filters as $key => $label)
                    <x-ui.chip :href="route('farmer.listings.index', $key === 'all' ? [] : ['status' => $key])" :selected="$status === $key">
                        {{ $label }} <span class="t-num">({{ \App\Models\CropListing::localiseDigits((string) $counts[$key]) }})</span>
                    </x-ui.chip>
                @endforeach
            </nav>
        @endif

        @if ($listings->isEmpty())
            @if ($counts['all'] === 0)
                <x-ui.empty-state icon="wheat" :title="__('You have no crop listings')" :description="__('Add your first crop so buyers can find it on the marketplace.')">
                    <x-ui.button size="md" icon="plus" :href="route('farmer.listings.create')">{{ __('Add crop listing') }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state icon="list" :title="$status === 'published' ? __('No published listings') : __('No hidden listings')">
                    <x-ui.button size="md" variant="secondary" :href="route('farmer.listings.index')">{{ __('Show all') }}</x-ui.button>
                </x-ui.empty-state>
            @endif
        @else
            <ul class="grid listing-grid">
                @foreach ($listings as $listing)
                    <li>
                        <article class="media-card listing-card">
                            <div class="media-card__media">
                                @if ($listing->coverPhoto)
                                    <img src="{{ $listing->coverPhoto->url('thumb') }}" alt="" width="{{ $listing->coverPhoto->thumbnail_width }}" height="{{ $listing->coverPhoto->thumbnail_height }}" loading="lazy" decoding="async">
                                @else
                                    <div class="media-card__placeholder"><x-ui.icon name="image" size="40" stroke="1.5" /></div>
                                @endif
                            </div>
                            <div class="media-card__body">
                                <div>@include('crop-listings._status-badge')</div>
                                <h2 class="t-title-3 listing-text"><a href="{{ route('farmer.listings.show', $listing) }}" class="listing-card__link">{{ $listing->crop_name }}</a></h2>
                                <x-ui.price :amount="$listing->price_per_unit" :unit="$listing->unitLabel()" />
                                <div class="media-card__meta">
                                    <span class="t-num">{{ __(':quantity :unit offered', ['quantity' => $listing->quantityForDisplay(), 'unit' => $listing->unitLabel()]) }}</span>
                                    <span class="listing-text"><x-ui.icon name="map-pin" size="14" /> {{ $listing->public_area }}</span>
                                    <span>{{ __('Updated :date', ['date' => $listing->updated_at->translatedFormat('j M Y')]) }}</span>
                                </div>
                                <div class="media-card__footer">
                                    <x-ui.button size="sm" variant="tertiary" icon="pencil" :href="route('farmer.listings.edit', $listing)">{{ __('Edit') }}<span class="visually-hidden"> {{ $listing->crop_name }}</span></x-ui.button>
                                </div>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>

            {{ $listings->links('partials.pagination') }}
        @endif
    </div>
</x-layouts.app>
