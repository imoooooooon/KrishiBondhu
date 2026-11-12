{{--
    Row inside <div class="list-group">. Pass :href to make it a link (a chevron is added).
    <x-ui.list-row icon="syringe" title="Insemination recorded" subtitle="Lal Bahadur · 3 Nov" :href="..." />
    Anything in the slot appears on the right (e.g. a badge or price).
--}}
@props(['title', 'subtitle' => null, 'icon' => null, 'href' => null])
@if ($href)
<a href="{{ $href }}" {{ $attributes->class(['list-row']) }}>
@else
<div {{ $attributes->class(['list-row']) }}>
@endif
    @if ($icon)<span class="list-row__icon"><x-ui.icon :name="$icon" /></span>@endif
    <span class="list-row__text">
        <span class="list-row__title">{{ $title }}</span>
        @if ($subtitle)<span class="list-row__subtitle">{{ $subtitle }}</span>@endif
    </span>
    <span class="list-row__trailing">
        {{ $slot }}
        @if ($href)<x-ui.icon name="chevron-right" size="18" />@endif
    </span>
@if ($href)
</a>
@else
</div>
@endif
