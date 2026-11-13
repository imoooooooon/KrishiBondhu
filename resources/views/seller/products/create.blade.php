{{--
    "Add product" (route seller.products.create, seller role). F25. Controller: Seller\AgriculturalProductController@create.
    submission_token: one per form, kept after a validation error, so sending the same form twice adds one product.
--}}
<x-layouts.app :title="__('Add product')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/shop.css').'?v='.@filemtime(public_path('css/shop.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Add product')" :subtitle="__('Describe one farm supply. It is published in the shop as soon as you submit.')">
        <x-slot:breadcrumbs>
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('seller.products.index') }}">{{ __('My products') }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <form method="POST" action="{{ route('seller.products.store') }}" class="stack stack-lg" id="product-form">
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">

        @include('seller.products._form')

        <x-ui.alert tone="info" :title="__('Your product will be visible to anyone.')">
            {{ __('Buyers see the name, brand, category, description, fixed price, stock and bulk order quantity. Your lowest bulk price and phone number are not shown.') }}
        </x-ui.alert>

        <div class="cluster">
            <x-ui.button type="submit" icon="upload">{{ __('Publish product') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('seller.products.index')">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>
</x-layouts.app>
