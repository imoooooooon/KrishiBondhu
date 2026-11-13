{{--
    "Edit product" (route seller.products.edit, owner only). F25. Controller: Seller\AgriculturalProductController@edit.
    Sends operation=save and the version this page was opened with. If the product changed meanwhile
    (another tab or phone), the save is refused with a 409 page instead of silently overwriting stock or prices.
--}}
<x-layouts.app :title="__('Edit product')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/shop.css').'?v='.@filemtime(public_path('css/shop.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Edit product')" :subtitle="$product->name" class="supply-text">
        <x-slot:breadcrumbs>
            <a href="{{ route('seller.products.index') }}">{{ __('My products') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('seller.products.show', $product) }}" class="supply-text">{{ $product->name }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <x-ui.alert tone="neutral" class="mb-6">
        <span class="cluster">@include('agricultural-products._status-badge') {{ __('Saving keeps this status. Use the buttons on the product page to hide or publish it.') }}</span>
    </x-ui.alert>

    <form method="POST" action="{{ route('seller.products.update', $product) }}" enctype="multipart/form-data" class="stack stack-lg" id="product-form">
        @csrf
        @method('PATCH')
        <input type="hidden" name="operation" value="save">
        <input type="hidden" name="version" value="{{ old('version', $product->version) }}">

        @include('seller.products._form', ['product' => $product])

        <div class="cluster">
            <x-ui.button type="submit" icon="check">{{ __('Save changes') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('seller.products.show', $product)">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>
</x-layouts.app>
