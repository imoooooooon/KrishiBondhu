{{--
    Public crop page (route marketplace.show). F1: everything a buyer needs to understand the offer.
    Controller: MarketplaceController@show. Only published listings reach this view.

    Buying (F5: cart, orders) and price offers (F3) are other features that are not built yet. Their forms already
    post to their planned routes (cart.items.store, marketplace.offers.store), so they start working when those
    features replace PlannedController. Until then, with JavaScript a message says "coming soon" (data-planned);
    without JavaScript the planned route answers with its "not built yet" page.

    Never shown publicly: the farmer's phone, email, name or exact address, and internal file paths.
--}}
@php
    $unit = $listing->unitLabel();
@endphp
<x-layouts.app :title="$listing->crop_name" :description="__(':crop from :area, ৳:price per :unit', ['crop' => $listing->crop_name, 'area' => $listing->public_area, 'price' => \App\Models\CropListing::trimDecimal((string) $listing->price_per_unit), 'unit' => $unit])">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/crop-listings.css').'?v='.@filemtime(public_path('css/crop-listings.css')) }}"></x-slot:head>

    <div class="container">
        <nav class="breadcrumbs mb-4" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('marketplace.index') }}">{{ __('Marketplace') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <span aria-current="page" class="listing-text">{{ $listing->crop_name }}</span>
        </nav>

        <div class="product">
            @include('crop-listings._gallery', [
                'selected' => $selectedPhoto,
                'photoLink' => fn ($photo) => route('marketplace.show', [$listing, 'photo' => $photo->id]),
            ])

            <aside class="card product-buy stack" aria-labelledby="product-title">
                <div class="cluster">
                    <x-ui.badge tone="harvest" icon="calendar">{{ __('Harvested :date', ['date' => $listing->harvest_date->translatedFormat('j M Y')]) }}</x-ui.badge>
                </div>
                <div class="stack stack-sm">
                    <h1 class="t-title-1 listing-text" id="product-title">{{ $listing->crop_name }}</h1>
                    <p class="t-callout t-muted cluster product-buy__seller">
                        <span class="listing-text"><x-ui.icon name="map-pin" size="16" /> {{ $listing->public_area }}</span>
                        <span><x-ui.icon name="user" size="16" /> {{ __('Listed by a farmer') }}</span>
                    </p>
                </div>

                <div class="product-buy__price">
                    <x-ui.price :amount="$listing->price_per_unit" :unit="$unit" class="t-display" />
                    <p class="t-callout t-muted">{{ __('Asking price per :unit. Delivery is not included.', ['unit' => $unit]) }}</p>
                </div>

                <p class="product-buy__stock t-body-strong">
                    <x-ui.icon name="package" /> <span class="t-num">{{ __(':quantity :unit available', ['quantity' => $listing->quantityForDisplay(), 'unit' => $unit]) }}</span>
                </p>

                @if ($isOwner)
                    <x-ui.alert tone="info" :title="__('This is your listing')">
                        {{ __('Buyers see this page. Change it from your listings.') }}
                    </x-ui.alert>
                    <x-ui.button icon="pencil" :href="route('farmer.listings.show', $listing)" block>{{ __('Manage this listing') }}</x-ui.button>
                @else
                    <form method="POST" action="{{ route('cart.items.store') }}" class="stack stack-sm"
                        data-planned="{{ __('Buying through Krishi Bondhu is coming soon. The cart and orders are not built yet.') }}">
                        @csrf
                        <input type="hidden" name="crop_listing_id" value="{{ $listing->id }}">
                        <x-ui.field name="cart_quantity" :label="__('How much do you need? (:unit)', ['unit' => $unit])" value="1" inputmode="decimal" autocomplete="off" id="product-quantity" />
                        <x-ui.button type="submit" icon="shopping-cart" block>{{ __('Add to cart') }}</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('marketplace.offers.store', $listing) }}"
                        data-planned="{{ __('Price offers are coming soon. Negotiation is not built yet.') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" icon="hand-coins" block>{{ __('Make an offer') }}</x-ui.button>
                    </form>
                    <p class="t-caption t-muted"><x-ui.icon name="info" size="14" /> {{ __('Cart, ordering and price offers are coming soon.') }}</p>
                @endif

                <div class="stack stack-sm">
                    <h2 class="t-overline">{{ __('Delivery options') }}</h2>
                    <ul class="listing-delivery">
                        @foreach ($listing->deliveryOptions as $option)
                            <li><x-ui.icon name="truck" size="16" /> {{ $option->label() }}</li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>

        <section class="product-details" aria-labelledby="details-title">
            <h2 class="t-title-2" id="details-title">{{ __('About this crop') }}</h2>
            <x-ui.card>
                @include('crop-listings._facts')
                <p class="t-caption t-muted mt-4">{{ __('Updated :date', ['date' => $listing->updated_at->translatedFormat('j M Y')]) }}</p>
            </x-ui.card>
        </section>

        @if ($moreListings->isNotEmpty())
            <section class="product-details" aria-labelledby="more-title">
                <div class="cluster cluster--between mb-4">
                    <h2 class="t-title-2" id="more-title">{{ __('More crops') }}</h2>
                    <x-ui.button size="md" variant="tertiary" icon-after="arrow-right" :href="route('marketplace.index')">{{ __('See all crops') }}</x-ui.button>
                </div>
                <ul class="grid market-grid">
                    @foreach ($moreListings as $more)
                        <li>@include('marketplace._card', ['listing' => $more])</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <x-slot:scripts><script src="{{ asset('js/crop-listings.js').'?v='.@filemtime(public_path('js/crop-listings.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
