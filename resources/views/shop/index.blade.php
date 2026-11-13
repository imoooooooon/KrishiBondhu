{{--
    Public farm-supplies shop (route shop.index). F25. Controller: ShopController@index.
    The filters are a plain GET form, so a search works without JavaScript and can be refreshed or shared.
--}}
<x-layouts.app :title="__('Farm supplies')" :description="__('Buy fertilizer, pesticide, seed and tools from agricultural sellers.')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/shop.css').'?v='.@filemtime(public_path('css/shop.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('Farm supplies')" :subtitle="__('Fertilizer, pesticide, seed and tools from agricultural sellers. Small purchases use a fixed price; large orders can accept bulk offers.')" />

        <form method="GET" action="{{ route('shop.index') }}" class="card supply-search" role="search" aria-label="{{ __('Search farm supplies') }}">
            <div class="supply-search__fields">
                <x-ui.field name="q" type="search" :label="__('Search')" :value="$filters['q'] ?? null" maxlength="100"
                    :placeholder="__('Product, brand or use, like urea')" />
                <x-ui.select name="category" :label="__('Category')" :value="$filters['category'] ?? ''"
                    :options="['' => __('All categories')] + \App\Models\AgriculturalProduct::categoryLabels()" />
                <x-ui.field name="min_price" :label="__('Lowest price')" :value="$filters['min_price'] ?? null" prefix="৳" inputmode="decimal" autocomplete="off" />
                <x-ui.field name="max_price" :label="__('Highest price')" :value="$filters['max_price'] ?? null" prefix="৳" inputmode="decimal" autocomplete="off" />
            </div>
            <div class="cluster cluster--between">
                <x-ui.checkbox name="in_stock" value="1" :label="__('In stock only')" :checked="$filters['in_stock'] ?? false" />
                <div class="cluster">
                    @if ($filters !== [])
                        <x-ui.button variant="tertiary" size="md" :href="route('shop.index')">{{ __('Clear filters') }}</x-ui.button>
                    @endif
                    <x-ui.button type="submit" size="md" icon="search">{{ __('Search') }}</x-ui.button>
                </div>
            </div>
        </form>

        <p class="t-callout t-muted supply-results" aria-live="polite">
            {{ trans_choice(':count product found|:count products found', $products->total(), ['count' => \App\Models\CropListing::localiseDigits((string) $products->total())]) }}
        </p>

        @if ($products->isEmpty())
            <x-ui.empty-state icon="package" :title="$filters === [] ? __('No farm supplies yet') : __('No products match your search')"
                :description="$filters === [] ? __('Sellers have not published any products yet. Please check again later.') : __('Try another word, another category or a wider price range.')">
                @if ($filters !== [])
                    <x-ui.button size="md" variant="secondary" :href="route('shop.index')">{{ __('Show all products') }}</x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <ul class="grid supply-grid supply-shop-grid">
                @foreach ($products as $product)
                    <li>@include('shop._card')</li>
                @endforeach
            </ul>

            {{ $products->links('partials.pagination') }}
        @endif
    </div>
</x-layouts.app>
