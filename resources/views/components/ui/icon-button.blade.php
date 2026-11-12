{{--
    Round icon-only button. A label is required (it is what screen readers announce).
    <x-ui.icon-button icon="bell" label="Notifications" :href="route('notifications.index')" :badge="3" />
--}}
@props(['icon', 'label', 'href' => null, 'badge' => null, 'plain' => false, 'type' => 'button'])
@php
    $classes = ['icon-btn', 'icon-btn--plain' => $plain];
    $accessibleName = $label.($badge ? ' ('.$badge.')' : '');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} aria-label="{{ $accessibleName }}" title="{{ $label }}">
        <x-ui.icon :name="$icon" />
        @if ($badge)<span class="icon-btn__badge" aria-hidden="true">{{ $badge }}</span>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }} aria-label="{{ $accessibleName }}" title="{{ $label }}">
        <x-ui.icon :name="$icon" />
        @if ($badge)<span class="icon-btn__badge" aria-hidden="true">{{ $badge }}</span>@endif
    </button>
@endif
