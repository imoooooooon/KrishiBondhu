{{--
    Money in Taka with tabular figures and lakh grouping; Bangla digits when the page language is Bangla.
    <x-ui.price :amount="125000" />            ->  ৳ 1,25,000
    <x-ui.price :amount="42.5" unit="kg" />    ->  ৳ 42.50 /kg
    Formatting only: amounts come from the controller or model and are never calculated in the view.
--}}
@props(['amount', 'unit' => null, 'decimals' => null])
@php
    $number = (float) $amount;
    $places = $decimals ?? (floor($number) == $number ? 0 : 2);
    $parts = explode('.', number_format(abs($number), $places, '.', ''));
    $whole = $parts[0];
    $lastThree = substr($whole, -3);
    $rest = substr($whole, 0, -3);
    $grouped = ($rest !== '' ? preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).',' : '').$lastThree;
    $formatted = ($number < 0 ? '−' : '').$grouped.(isset($parts[1]) ? '.'.$parts[1] : '');
    if (app()->getLocale() === 'bn') {
        $formatted = strtr($formatted, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    }
@endphp
<span {{ $attributes->class(['price']) }}><span class="price__currency">৳</span><span>{{ $formatted }}</span>@if ($unit)<span class="price__unit">/{{ $unit }}</span>@endif</span>
