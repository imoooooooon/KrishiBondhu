{{--
    409 Conflict (rendered by App\Exceptions\AgriculturalProductConflictException). F25.
    The product changed after this seller opened their form, so nothing was saved. The latest saved product
    and what they sent are shown side by side, so they can copy their changes into a fresh edit form.
    $product: latest AgriculturalProduct (this is the owner's page, so the private bulk price may be shown).
    $submitted: the fields they sent (empty for publish/unpublish/remove).
--}}
@php
    $labels = [
        'name' => __('Product name'),
        'brand' => __('Brand'),
        'category' => __('Category'),
        'description' => __('Description'),
        'unit_code' => __('Sold per'),
        'unit_price' => __('Fixed price per unit'),
        'stock_quantity' => __('Stock available'),
        'accepts_bulk_offers' => __('Accept bulk offers'),
        'bulk_min_quantity' => __('Bulk order from'),
        'bulk_min_unit_price' => __('Your lowest bulk price per unit'),
    ];
    $display = function (string $key, mixed $value) {
        return match ($key) {
            'category' => \App\Models\AgriculturalProduct::categoryLabels()[$value] ?? (is_scalar($value) ? (string) $value : ''),
            'unit_code' => \App\Models\AgriculturalProduct::unitLabels()[$value] ?? (is_scalar($value) ? (string) $value : ''),
            'accepts_bulk_offers' => (string) $value === '1' ? __('Yes') : __('No'),
            default => is_scalar($value) ? (string) $value : '',
        };
    };
@endphp
<x-layouts.app :title="__('This product changed')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/shop.css').'?v='.@filemtime(public_path('css/shop.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('This product changed after you opened it')">
            <x-slot:breadcrumbs><a href="{{ route('seller.products.index') }}">{{ __('My products') }}</a></x-slot:breadcrumbs>
        </x-ui.page-header>

        <x-ui.alert tone="warning" role="alert" class="mb-6" :title="__('Your changes were not saved.')">
            {{ __('It was changed in another tab or on another phone. Compare the two versions below. To keep your changes, open the latest edit form and enter them again.') }}
        </x-ui.alert>

        <div class="grid-2 supply-compare">
            <x-ui.card class="stack">
                <div class="cluster cluster--between">
                    <h2 class="t-title-3">{{ __('Latest saved version') }}</h2>
                    @include('agricultural-products._status-badge')
                </div>
                <p class="t-title-3 supply-text">{{ $product->name }}</p>
                @include('agricultural-products._facts', ['showPrivate' => true])
            </x-ui.card>

            <x-ui.card tone="surface" class="stack">
                <h2 class="t-title-3">{{ __('What you sent') }}</h2>
                @if ($submitted === [])
                    <p class="t-callout t-muted">{{ __('You pressed a button (publish, unpublish or remove) on an old copy of the page. No text was sent.') }}</p>
                @else
                    <dl class="supply-facts">
                        @foreach ($labels as $key => $label)
                            @if (array_key_exists($key, $submitted))
                                <div><dt>{{ $label }}</dt><dd class="supply-text supply-text--lines">{{ $display($key, $submitted[$key]) }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                @endif
            </x-ui.card>
        </div>

        <div class="cluster mt-8">
            <x-ui.button icon="pencil" :href="route('seller.products.edit', $product)" target="_blank" rel="noopener">{{ __('Open the latest edit form (new tab)') }}</x-ui.button>
            <x-ui.button variant="secondary" :href="route('seller.products.show', $product)">{{ __('Back to the product') }}</x-ui.button>
        </div>
    </div>
</x-layouts.app>
