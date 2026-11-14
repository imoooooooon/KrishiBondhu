{{--
    "Record a price" (route contributor.prices.create, market price contributor role). F11.
    Controller: Contributor\MarketPriceController@create. Sending the same form twice records one price.
--}}
<x-layouts.app :title="__('Record a market price')" narrow>
    <x-ui.page-header :title="__('Record a market price')" :subtitle="__('Enter a price you saw for a farm product, or your estimate for tomorrow. Every entry is kept as price history.')">
        <x-slot:breadcrumbs>
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('contributor.prices.index') }}">{{ __('My market prices') }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <form method="POST" action="{{ route('contributor.prices.store') }}" class="stack stack-lg" id="price-form">
        @csrf

        @include('contributor.prices._form')

        <x-ui.alert tone="info" :title="__('Your price will be public.')">
            {{ __('Anyone can see the market, crop, price and date. Your name and phone number are not shown.') }}
        </x-ui.alert>

        <div class="cluster">
            <x-ui.button type="submit" icon="check">{{ __('Record price') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('contributor.prices.index')">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>
</x-layouts.app>
