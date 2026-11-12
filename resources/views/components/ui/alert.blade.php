{{--
    Inline alert / banner.
    <x-ui.alert tone="warning" title="Price below market">Your price is 18% below today's average in Bogura.</x-ui.alert>
    tone: neutral | success | info | warning | danger. Add role="alert" only for errors shown after an action.
--}}
@props(['tone' => 'info', 'title' => null, 'icon' => null])
@php
    $defaultIcons = ['success' => 'circle-check', 'warning' => 'triangle-alert', 'danger' => 'circle-alert', 'info' => 'info', 'neutral' => 'info'];
    $icon = $icon ?? ($defaultIcons[$tone] ?? 'info');
@endphp
<div {{ $attributes->class(['alert', 'alert--'.$tone => $tone !== 'neutral']) }}>
    <x-ui.icon :name="$icon" class="alert__icon" />
    <div class="alert__body">
        @if ($title)<p class="alert__title">{{ $title }}</p>@endif
        <div class="t-callout">{{ $slot }}</div>
    </div>
    @isset($action)<div>{{ $action }}</div>@endisset
</div>
