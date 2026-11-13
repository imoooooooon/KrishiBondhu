{{--
    "My products" (route seller.products.index, seller role). F25. Controller: Seller\AgriculturalProductController@index.
    Filters are plain links (?status=published), so they work without JavaScript and stay in the page links.
    Each card: the title links to the product; "Edit" is a separate link (no button inside a link).
--}}
@php
    $filters = [
        'all' => __('All'),
        'published' => __('Published'),
        'unpublished' => __('Hidden'),
    ];
@endphp
<x-layouts.app :title="__('My products')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/shop.css').'?v='.@filemtime(public_path('css/shop.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('My products')" :subtitle="__('Farm supplies you sell. Keep prices and stock up to date.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" icon="plus" :href="route('seller.products.create')">{{ __('Add product') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('product_warning'))
            <x-ui.alert tone="warning" class="mb-6">{{ session('product_warning') }}</x-ui.alert>
        @endif
        @if ($unknownFilter)
            <x-ui.alert tone="info" class="mb-6">{{ __('That filter does not exist, so all your products are shown.') }}</x-ui.alert>
        @endif

        @if ($counts['all'] > 0)
            <nav class="cluster mb-6" aria-label="{{ __('Filter products') }}">
                @foreach ($filters as $key => $label)
                    <x-ui.chip :href="route('seller.products.index', $key === 'all' ? [] : ['status' => $key])" :selected="$status === $key">
                        {{ $label }} <span class="t-num">({{ \App\Models\CropListing::localiseDigits((string) $counts[$key]) }})</span>
                    </x-ui.chip>
                @endforeach
            </nav>
        @endif

        @if ($products->isEmpty())
            @if ($counts['all'] === 0)
                <x-ui.empty-state icon="package" :title="__('You have no products')" :description="__('Add your first fertilizer, pesticide, seed or tool so buyers can find it.')">
                    <x-ui.button size="md" icon="plus" :href="route('seller.products.create')">{{ __('Add product') }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state icon="list" :title="$status === 'published' ? __('No published products') : __('No hidden products')">
                    <x-ui.button size="md" variant="secondary" :href="route('seller.products.index')">{{ __('Show all') }}</x-ui.button>
                </x-ui.empty-state>
            @endif
        @else
            <ul class="grid supply-grid">
                @foreach ($products as $product)
                    <li>
                        <article class="media-card supply-card">
                            <div class="media-card__media">
                                @if ($product->hasPhoto())
                                    <img src="{{ $product->photoUrl('thumb') }}" alt="" loading="lazy" decoding="async">
                                @else
                                    <div class="media-card__placeholder" data-decorative><x-ui.icon name="package" size="40" stroke="1.5" /></div>
                                @endif
                            </div>
                            <div class="media-card__body">
                                <div class="cluster">@include('agricultural-products._status-badge') @include('agricultural-products._stock-badge')</div>
                                <h2 class="t-title-3 supply-text"><a href="{{ route('seller.products.show', $product) }}" class="supply-card__link">{{ $product->name }}</a></h2>
                                <x-ui.price :amount="$product->unit_price" :unit="$product->unitLabel()" />
                                <div class="media-card__meta">
                                    @if ($product->public_area)
                                        <span class="supply-text supply-inline-icon"><x-ui.icon name="map-pin" size="14" /> {{ $product->public_area }}</span>
                                    @endif
                                    <span>{{ $product->categoryLabel() }}</span>
                                    @if ($product->acceptsBulkOffers())
                                        <span>{{ __('Bulk offers from :quantity :unit', ['quantity' => $product->bulkMinQuantityForDisplay(), 'unit' => $product->unitLabel()]) }}</span>
                                    @endif
                                    <span>{{ __('Updated :date', ['date' => $product->updated_at->translatedFormat('j M Y')]) }}</span>
                                </div>
                                <div class="media-card__footer">
                                    <x-ui.button size="sm" variant="tertiary" icon="pencil" :href="route('seller.products.edit', $product)">{{ __('Edit') }}<span class="visually-hidden"> {{ $product->name }}</span></x-ui.button>
                                </div>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>

            {{ $products->links('partials.pagination') }}
        @endif
    </div>
</x-layouts.app>
