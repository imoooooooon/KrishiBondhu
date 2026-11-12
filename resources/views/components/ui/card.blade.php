{{--
    Card: grey element on the white canvas (two-tone, no border).
    <x-ui.card>...</x-ui.card>
    tone: card (default) | surface | brand | harvest | earth
    Optional named slots: title, actions. Pass :href to make the whole card a link.
--}}
@props(['tone' => 'card', 'href' => null])
@php
    $classes = ['card', 'card--'.$tone => $tone !== 'card', 'card--interactive' => (bool) $href];
@endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->class($classes) }}>
@else
<div {{ $attributes->class($classes) }}>
@endif
    @if (isset($title) || isset($actions))
        <div class="card__header">
            @isset($title)<h3 class="t-title-3">{{ $title }}</h3>@endisset
            @isset($actions)<div class="cluster">{{ $actions }}</div>@endisset
        </div>
    @endif
    {{ $slot }}
@if ($href)
</a>
@else
</div>
@endif
