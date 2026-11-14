{{--
    Health hub (route: health.index, public). Entry point to the crop and animal health area, F15–F19.
    Built areas are links; F16 and F17 are still planned, so they are shown as "Coming soon" and are not links.
    Pages behind login (soil guidance, treatments) send guests to the login page as usual.
    Text is English in the source and translated through lang/bn.json.
--}}
@php
    $areas = [
        ['route' => 'guidance.soil.create', 'icon' => 'sprout', 'tone' => 'harvest',
            'title' => __('Soil and fertilizer guidance'),
            'copy' => __('Describe your crop and soil to get general soil-care and fertilizer guidance. A photo is optional.')],
        ['route' => 'vets.index', 'icon' => 'stethoscope', 'tone' => 'accent',
            'title' => __('Find a veterinarian'),
            'copy' => __('Search veterinary doctors by district and animal, see their contact details, and request a farm visit.')],
        ['route' => 'treatments.index', 'icon' => 'heart-pulse', 'tone' => 'earth',
            'title' => __('Treatment history and reminders'),
            'copy' => __('Keep notes on crop and animal treatments and get a reminder on the follow-up date.')],
    ];
    $planned = [
        ['icon' => 'bug', 'title' => __('Crop disease identification'),
            'copy' => __('Upload a photo of a sick crop to learn the likely disease and what to do.')],
        ['icon' => 'cow-head', 'title' => __('Animal health guidance'),
            'copy' => __('Enter an animal’s symptoms to get first-step guidance and know when to call a vet.')],
    ];
@endphp
<x-layouts.app :title="__('Crop and animal health')">
    <div class="container">
        <x-ui.page-header
            :title="__('Crop and animal health')"
            :subtitle="__('Guidance for your soil, crops and animals, and a veterinary doctor when you need one.')" />

        <div class="grid">
            @foreach ($areas as $area)
                <x-ui.card :href="route($area['route'])" class="area-card">
                    <span class="area-card__icon area-card__icon--{{ $area['tone'] }}"><x-ui.icon :name="$area['icon']" size="24" /></span>
                    <h2 class="t-title-3">{{ $area['title'] }}</h2>
                    <p class="t-callout t-muted">{{ $area['copy'] }}</p>
                    <span class="area-card__link">{{ __('Open') }} <x-ui.icon name="arrow-right" size="16" /></span>
                </x-ui.card>
            @endforeach

            @foreach ($planned as $area)
                <x-ui.card tone="surface" class="area-card">
                    <span class="area-card__icon area-card__icon--accent"><x-ui.icon :name="$area['icon']" size="24" /></span>
                    <h2 class="t-title-3">{{ $area['title'] }}</h2>
                    <p class="t-callout t-muted">{{ $area['copy'] }}</p>
                    <x-ui.badge icon="clock">{{ __('Coming soon') }}</x-ui.badge>
                </x-ui.card>
            @endforeach
        </div>

        <p class="t-callout t-muted mt-4">{{ __('Guidance here is general advice. For a sick animal or a serious crop problem, contact a veterinary doctor or your local agriculture officer.') }}</p>
    </div>
</x-layouts.app>
