{{--
    Public product page (route shop.show). F25. Controller: ShopController@show.
    It separates the two ways to buy:
      - fixed price, for small purchases (the cart and orders are F5, checkout is F4);
      - bulk offers, for large orders, if the seller accepts them (negotiation is F3).
    Those features are not built yet, so the buttons are disabled with an explanation instead of failing later.
    The seller's private lowest bulk price is never shown here (_facts is included without showPrivate).
--}}
<x-layouts.app :title="$product->name" :description="\Illuminate\Support\Str::limit($product->description, 150)">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/shop.css').'?v='.@filemtime(public_path('css/shop.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="$product->name" class="supply-text">
            <x-slot:breadcrumbs>
                <a href="{{ route('shop.index') }}">{{ __('Farm supplies') }}</a>
                <x-ui.icon name="chevron-right" size="14" />
                <a href="{{ route('shop.index', ['category' => $product->category]) }}">{{ $product->categoryLabel() }}</a>
            </x-slot:breadcrumbs>
        </x-ui.page-header>

        @if ($isOwner)
            <x-ui.alert tone="info" class="mb-6" :title="__('This is your product')">
                {{ __('Buyers see this page. Your lowest bulk price is not shown here.') }}
                <a href="{{ route('seller.products.show', $product) }}">{{ __('Manage this product') }}</a>
            </x-ui.alert>
        @endif

        <div class="supply-layout">
            <div class="stack">
                @include('agricultural-products._photo')
                <x-ui.card class="stack">
                    <h2 class="t-title-3">{{ __('Product details') }}</h2>
                    @include('agricultural-products._facts')
                    <p class="t-caption t-muted"><x-ui.icon name="user" size="14" /> {{ __('Sold by an agricultural product seller on Krishi Bondhu') }}</p>
                    <p class="t-caption t-muted supply-inline-icon"><x-ui.icon name="clock" size="14" /> <time datetime="{{ $product->published_at?->toIso8601String() }}">{{ __('Listed :date', ['date' => $product->listedAtForDisplay()]) }}</time></p>
                </x-ui.card>
            </div>

            <div class="stack">
                {{-- Small purchase at the fixed price --}}
                <section class="card stack stack-sm" aria-labelledby="buy-fixed">
                    <h2 class="t-title-3" id="buy-fixed">{{ __('Buy at the fixed price') }}</h2>
                    <x-ui.price :amount="$product->unit_price" :unit="$product->unitLabel()" class="t-display supply-price" />
                    <p class="t-callout t-muted">{{ __('For small purchases. Everyone pays this price per :unit; there is no bargaining.', ['unit' => $product->unitLabel()]) }}</p>
                    <div>@include('agricultural-products._stock-badge')</div>
                    @if ($product->isOutOfStock())
                        <p class="t-callout">{{ __('This product is out of stock and cannot be bought until the seller restocks.') }}</p>
                    @endif
                    <div class="supply-buy-delivery stack stack-sm">
                        <h3 class="t-body-strong">{{ __('Delivery options') }}</h3>
                        @if ($product->public_area)
                            <p class="t-callout supply-text supply-inline-icon"><x-ui.icon name="map-pin" size="16" /> {{ __('Sold from :area', ['area' => $product->public_area]) }}</p>
                        @endif
                        @include('agricultural-products._delivery')
                        <p class="t-caption t-muted">{{ __('Delivery cost is not included in the price.') }}</p>
                    </div>
                    <x-ui.button icon="shopping-cart" block disabled>{{ __('Add to cart') }}</x-ui.button>
                </section>

                {{-- Large order: a bulk offer, only when the seller accepts them --}}
                <section class="card stack stack-sm" aria-labelledby="buy-bulk">
                    <h2 class="t-title-3" id="buy-bulk">{{ __('Bulk order') }}</h2>
                    @if ($product->acceptsBulkOffers())
                        <p class="t-callout">
                            {{ __('For :quantity :unit or more, you can offer your own price per :unit. The seller can accept, reject or reply with another price.', ['quantity' => $product->bulkMinQuantityForDisplay(), 'unit' => $product->unitLabel()]) }}
                        </p>
                        @unless ($product->bulkAvailableNow())
                            <p class="t-callout t-muted">{{ __('There is not enough stock for a bulk order right now.') }}</p>
                        @endunless
                        <x-ui.button variant="secondary" icon="hand-coins" block disabled>{{ __('Make a bulk offer') }}</x-ui.button>
                    @else
                        <p class="t-callout t-muted">{{ __('This seller sells at the fixed price only. Bulk offers are not accepted for this product.') }}</p>
                    @endif
                </section>

                <x-ui.alert tone="neutral">
                    {{ __('Ordering online is not available yet: the cart, checkout and bulk offers are still being built. They will work on this page when they are ready.') }}
                </x-ui.alert>
            </div>
        </div>

        @if ($moreProducts->isNotEmpty())
            <section class="supply-more" aria-labelledby="more-title">
                <h2 class="t-title-2" id="more-title">{{ __('More :category', ['category' => $product->categoryLabel()]) }}</h2>
                <ul class="grid supply-grid supply-shop-grid">
                    @foreach ($moreProducts as $other)
                        <li>@include('shop._card', ['product' => $other])</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
