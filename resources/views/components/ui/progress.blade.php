{{-- <x-ui.progress :value="7" :max="10" label="Workers booked" /> --}}
@props(['value', 'max' => 100, 'label'])
@php
    $percent = $max > 0 ? max(0, min(100, round($value / $max * 100))) : 0;
@endphp
<div {{ $attributes->class(['progress']) }} role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="{{ $max }}" aria-valuenow="{{ $value }}">
    <div class="progress__bar" style="width: {{ $percent }}%"></div>
</div>
