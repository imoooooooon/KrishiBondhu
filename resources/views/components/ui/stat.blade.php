{{--
    Stat tile content (place inside <x-ui.card>).
    <x-ui.stat label="Today's average (rice)" value="৳42/kg" change="+3% this week" trend="up" />
    Gains and losses always carry a sign and an icon, never colour alone.
--}}
@props(['label', 'value', 'change' => null, 'trend' => 'up'])
<div {{ $attributes->class(['stat']) }}>
    <p class="t-caption t-muted">{{ $label }}</p>
    <p class="stat__value">{{ $value }}</p>
    @if ($change)
        <p class="stat__change stat__change--{{ $trend === 'down' ? 'down' : 'up' }}">
            <x-ui.icon :name="$trend === 'down' ? 'minus' : 'trending-up'" size="14" stroke="2" />
            <span>{{ $change }}</span>
        </p>
    @endif
</div>
