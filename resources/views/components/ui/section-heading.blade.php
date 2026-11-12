{{--
    Website section heading.
    <x-ui.section-heading eyebrow="Market prices" title="Know the price before you sell" center>Supporting copy.</x-ui.section-heading>
--}}
@props(['title', 'eyebrow' => null, 'center' => false, 'level' => 2])
<div {{ $attributes->class(['section-heading', 'section-heading--center' => $center]) }}>
    @if ($eyebrow)<p class="eyebrow t-overline">{{ $eyebrow }}</p>@endif
    <h{{ $level }} class="t-display">{{ $title }}</h{{ $level }}>
    @if ($slot->isNotEmpty())<p class="t-muted max-prose">{{ $slot }}</p>@endif
</div>
