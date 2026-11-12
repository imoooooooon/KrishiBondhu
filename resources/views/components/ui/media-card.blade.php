{{--
    Media card for crop listings, products, markets, vets and jobs.
    <x-ui.media-card :href="..." title="Aman rice (BR-11)" :image="$url" image-alt="..." badge="Available" badge-tone="success">
        <x-slot:meta><span>Rajshahi</span><span>Harvested 2 Nov</span></x-slot:meta>
        <x-slot:footer><x-ui.price :amount="42" unit="kg" /></x-slot:footer>
    </x-ui.media-card>
    Without an image, a calm tinted placeholder with an icon is shown (never a stock photo).
--}}
@props(['title', 'href' => null, 'image' => null, 'imageAlt' => '', 'icon' => 'sprout', 'badge' => null, 'badgeTone' => 'neutral'])
@if ($href)
<a href="{{ $href }}" {{ $attributes->class(['media-card']) }}>
@else
<article {{ $attributes->class(['media-card']) }}>
@endif
    <div class="media-card__media">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy" decoding="async">
        @else
            <div class="media-card__placeholder" data-decorative><x-ui.icon :name="$icon" size="40" stroke="1.5" /></div>
        @endif
        @if ($badge)<x-ui.badge :tone="$badgeTone">{{ $badge }}</x-ui.badge>@endif
    </div>
    <div class="media-card__body">
        <h3 class="t-title-3">{{ $title }}</h3>
        @isset($meta)<div class="media-card__meta">{{ $meta }}</div>@endisset
        {{ $slot }}
        @isset($footer)<div class="media-card__footer">{{ $footer }}</div>@endisset
    </div>
@if ($href)
</a>
@else
</article>
@endif
