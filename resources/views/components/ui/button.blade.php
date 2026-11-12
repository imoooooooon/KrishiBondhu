{{--
    Button. One primary per screen region.
    <x-ui.button>Save listing</x-ui.button>
    <x-ui.button :href="route('marketplace.index')" variant="secondary" icon="search">Browse crops</x-ui.button>
    variant: primary | accent (on brand surfaces) | secondary | tertiary | danger
    size: lg (48) | md (40) | sm (32); md and sm keep a 44 px touch target.
--}}
@props(['variant' => 'primary', 'size' => 'lg', 'href' => null, 'icon' => null, 'iconAfter' => null, 'block' => false, 'type' => 'button', 'disabled' => false])
@php
    $classes = ['btn', 'btn--'.$variant, 'btn--'.$size => $size !== 'lg', 'btn--block' => $block];
    $iconSize = $size === 'lg' ? 20 : 18;
@endphp
@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-ui.icon :name="$icon" :size="$iconSize" />@endif
        <span>{{ $slot }}</span>
        @if ($iconAfter)<x-ui.icon :name="$iconAfter" :size="$iconSize" />@endif
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($classes) }}>
        @if ($icon)<x-ui.icon :name="$icon" :size="$iconSize" />@endif
        <span>{{ $slot }}</span>
        @if ($iconAfter)<x-ui.icon :name="$iconAfter" :size="$iconSize" />@endif
    </button>
@endif
