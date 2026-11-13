{{--
    409 Conflict (rendered by App\Exceptions\CropListingConflictException). F1 spec §8.4 and §13.2.
    The listing changed after this farmer opened their form, so nothing was saved. The latest saved listing
    and the text they sent are shown side by side, so they can copy their changes into a fresh edit form.
    $listing: latest CropListing. $submitted: the text fields they sent (may be empty for publish/remove).
--}}
@php
    $labels = [
        'crop_name' => __('Crop name'),
        'quality_description' => __('Quality'),
        'harvest_date' => __('Harvest date'),
        'quantity' => __('Quantity'),
        'unit_code' => __('Unit'),
        'price_per_unit' => __('Price per unit'),
        'public_area' => __('Area'),
        'delivery_options' => __('Delivery options'),
        'delivery_notes' => __('Delivery notes'),
    ];
    $display = function (string $key, mixed $value) {
        if ($key === 'delivery_options') {
            return collect((array) $value)->map(fn ($code) => \App\Models\CropListing::deliveryLabels()[$code] ?? $code)->implode(', ');
        }
        if ($key === 'unit_code') {
            return \App\Models\CropListing::unitLabels()[$value] ?? $value;
        }

        return is_scalar($value) ? (string) $value : '';
    };
@endphp
<x-layouts.app :title="__('This listing changed')">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/crop-listings.css') }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('This listing changed after you opened it')">
            <x-slot:breadcrumbs><a href="{{ route('farmer.listings.index') }}">{{ __('My crop listings') }}</a></x-slot:breadcrumbs>
        </x-ui.page-header>

        <x-ui.alert tone="warning" role="alert" class="mb-6" :title="__('Your changes were not saved.')">
            {{ __('It was changed in another tab or on another phone. Compare the two versions below. To keep your changes, open the latest edit form and enter them again.') }}
        </x-ui.alert>

        <div class="grid-2 listing-compare">
            <x-ui.card class="stack">
                <div class="cluster cluster--between">
                    <h2 class="t-title-3">{{ __('Latest saved version') }}</h2>
                    @include('crop-listings._status-badge')
                </div>
                <p class="t-title-3 listing-text">{{ $listing->crop_name }}</p>
                @include('crop-listings._facts')
            </x-ui.card>

            <x-ui.card tone="surface" class="stack">
                <h2 class="t-title-3">{{ __('What you sent') }}</h2>
                @if ($submitted === [])
                    <p class="t-callout t-muted">{{ __('You pressed a button (publish, unpublish or remove) on an old copy of the page. No text was sent.') }}</p>
                @else
                    <dl class="listing-facts">
                        @foreach ($labels as $key => $label)
                            @if (array_key_exists($key, $submitted))
                                <div><dt>{{ $label }}</dt><dd class="listing-text listing-text--lines">{{ $display($key, $submitted[$key]) }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                    <p class="t-caption t-muted">{{ __('New photos you selected were not kept. Select them again in the edit form.') }}</p>
                @endif
            </x-ui.card>
        </div>

        <div class="cluster mt-8">
            <x-ui.button icon="pencil" :href="route('farmer.listings.edit', $listing)" target="_blank" rel="noopener">{{ __('Open the latest edit form (new tab)') }}</x-ui.button>
            <x-ui.button variant="secondary" :href="route('farmer.listings.show', $listing)">{{ __('Back to the listing') }}</x-ui.button>
        </div>
    </div>
</x-layouts.app>
