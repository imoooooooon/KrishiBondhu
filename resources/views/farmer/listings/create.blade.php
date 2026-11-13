{{--
    "Add crop listing" (route farmer.listings.create, Farmer role). F1. Controller: Farmer\CropListingController@create.
    submission_token: one per form, kept after a validation error, so sending the same form twice publishes once.
--}}
<x-layouts.app :title="__('Add crop listing')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/crop-listings.css') }}"></x-slot:head>

    <x-ui.page-header :title="__('Add crop listing')" :subtitle="__('Describe one crop lot. It is published on the marketplace as soon as you submit.')">
        <x-slot:breadcrumbs>
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('farmer.listings.index') }}">{{ __('My crop listings') }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <form method="POST" action="{{ route('farmer.listings.store') }}" enctype="multipart/form-data" class="stack stack-lg" id="listing-form" data-listing-form>
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">

        @include('farmer.listings._form')

        <x-ui.alert tone="info" :title="__('Your listing and crop photos will be visible to anyone.')">
            {{ __('Buyers see the crop, price, quantity, quality, harvest date, area and delivery options. Your phone number is not shown.') }}
        </x-ui.alert>

        <div class="cluster">
            <x-ui.button type="submit" icon="upload" data-submit-once data-busy-label="{{ __('Publishing…') }}">{{ __('Publish listing') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('farmer.listings.index')">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>

    <x-slot:scripts><script src="{{ asset('js/crop-listings.js') }}" defer></script></x-slot:scripts>
</x-layouts.app>
