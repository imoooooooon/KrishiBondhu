{{--
    Status badge: always text (+ optional icon), never colour alone.
    <x-ui.badge tone="success" icon="circle-check">Verified</x-ui.badge>
    tone: neutral | success | info | warning | danger | highlight | harvest | earth
--}}
@props(['tone' => 'neutral', 'icon' => null])
<span {{ $attributes->class(['badge', 'badge--'.$tone => $tone !== 'neutral']) }}>
    @if ($icon)<x-ui.icon :name="$icon" size="14" stroke="2" />@endif
    {{ $slot }}
</span>
