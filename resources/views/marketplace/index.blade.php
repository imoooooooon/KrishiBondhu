{{--
    Crop marketplace storefront (route marketplace.index, public). F1: published listings, newest first.
    Controller: MarketplaceController@index.
    Search and filters belong to F2 and are not built yet: the search box is shown only when JavaScript runs,
    and says so in a message instead of pretending to search (data-planned, public/js/crop-listings.js).
--}}
@php
    $canSell = auth()->user()?->can('create', \App\Models\CropListing::class) ?? false;
@endphp
<x-layouts.app :title="__('Marketplace')" :description="__('Buy crops directly from farmers in Bangladesh. Prices per unit set by the farmers, with real photos of each lot.')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/crop-listings.css').'?v='.@filemtime(public_path('css/crop-listings.css')) }}"></x-slot:head>

    <div class="container">
        <section class="card card--brand market-hero" aria-labelledby="market-title">
            <div class="market-hero__copy">
                <p class="t-overline market-hero__eyebrow">{{ __('Krishi Bondhu marketplace') }}</p>
                <h1 class="t-title-1" id="market-title">{{ __('Fresh crops, straight from farmers') }}</h1>
                <p class="t-muted">{{ __('Each farmer sets the price per unit and shows photos of the actual crop. Delivery is arranged with the farmer.') }}</p>
            </div>
            <div class="cluster">
                @if ($canSell)
                    <x-ui.button variant="accent" icon="plus" :href="route('farmer.listings.create')">{{ __('Sell your crop') }}</x-ui.button>
                    <x-ui.button variant="secondary" icon="list" :href="route('farmer.listings.index')">{{ __('Manage my listings') }}</x-ui.button>
                @endif
                @guest
                    <x-ui.button variant="accent" icon="sprout" :href="route('register')">{{ __('Join to sell your crop') }}</x-ui.button>
                @endguest
            </div>
        </section>

        <ul class="market-points" aria-label="{{ __('How the marketplace works') }}">
            <li><x-ui.icon name="hand-coins" /> <span>{{ __('Price per unit, set by the farmer') }}</span></li>
            <li><x-ui.icon name="image" /> <span>{{ __('Photos of the actual crop lot') }}</span></li>
            <li><x-ui.icon name="truck" /> <span>{{ __('Pickup or delivery agreed with the farmer') }}</span></li>
        </ul>

        <div class="market-toolbar">
            <h2 class="t-title-2">{{ __('Available crops') }}</h2>
            <form class="market-search js-only" role="search" aria-label="{{ __('Search crops') }}" data-planned="{{ __('Crop search and filters are coming soon. For now, browse the newest crops below.') }}">
                <label class="visually-hidden" for="market-search">{{ __('Search crops') }}</label>
                <div class="input-group">
                    <span class="input-group__addon"><x-ui.icon name="search" size="18" /></span>
                    <input id="market-search" type="search" class="input" placeholder="{{ __('Search crops, like potato or rice') }}" autocomplete="off">
                </div>
                <x-ui.button type="submit" size="md" variant="secondary">{{ __('Search') }}</x-ui.button>
            </form>
        </div>

        @if ($listings->isEmpty())
            <x-ui.empty-state icon="wheat" :title="__('No crops are listed yet')" :description="__('Farmers have not published any crops yet. Please check again soon.')">
                @if ($canSell)
                    <x-ui.button size="md" icon="plus" :href="route('farmer.listings.create')">{{ __('Add crop listing') }}</x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <p class="t-callout t-muted mb-4" role="status">{{ trans_choice(':count crop available|:count crops available', $listings->total(), ['count' => \App\Models\CropListing::localiseDigits((string) $listings->total())]) }}</p>

            <ul class="grid market-grid">
                @foreach ($listings as $listing)
                    <li>@include('marketplace._card')</li>
                @endforeach
            </ul>

            {{ $listings->links('partials.pagination') }}
        @endif
    </div>

    <x-slot:scripts><script src="{{ asset('js/crop-listings.js').'?v='.@filemtime(public_path('js/crop-listings.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
