{{--
    "Edit listing" (route farmer.listings.edit, owner only). F1. Controller: Farmer\CropListingController@edit.
    Sends operation=save and the version this page was opened with. If the listing changed meanwhile
    (another tab or phone), the save is refused with a 409 page instead of silently overwriting.
--}}
<x-layouts.app :title="__('Edit listing')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/crop-listings.css').'?v='.@filemtime(public_path('css/crop-listings.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Edit listing')" :subtitle="$listing->crop_name" class="listing-text">
        <x-slot:breadcrumbs>
            <a href="{{ route('farmer.listings.index') }}">{{ __('My crop listings') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('farmer.listings.show', $listing) }}" class="listing-text">{{ $listing->crop_name }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <x-ui.alert tone="neutral" class="mb-6">
        <span class="cluster">@include('crop-listings._status-badge') {{ __('Saving keeps this status. Use the buttons on the listing page to hide or publish it.') }}</span>
    </x-ui.alert>

    <form method="POST" action="{{ route('farmer.listings.update', $listing) }}" enctype="multipart/form-data" class="stack stack-lg" id="listing-form" data-listing-form>
        @csrf
        @method('PATCH')
        <input type="hidden" name="operation" value="save">
        <input type="hidden" name="version" value="{{ old('version', $listing->version) }}">

        @include('farmer.listings._form', ['listing' => $listing])

        <div class="cluster">
            <x-ui.button type="submit" icon="check" data-submit-once data-busy-label="{{ __('Saving…') }}">{{ __('Save changes') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('farmer.listings.show', $listing)">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>

    <x-slot:scripts><script src="{{ asset('js/crop-listings.js').'?v='.@filemtime(public_path('js/crop-listings.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
