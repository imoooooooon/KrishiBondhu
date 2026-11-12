{{--
    Filter chip. As a link: <x-ui.chip :href="..." :selected="request('quality') === 'a'">Grade A</x-ui.chip>
    As a toggle button (needs your own script to toggle aria-pressed): <x-ui.chip>Organic</x-ui.chip>
--}}
@props(['href' => null, 'selected' => false, 'icon' => null])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['chip']) }} @if ($selected) aria-current="true" @endif>
        @if ($icon)<x-ui.icon :name="$icon" size="16" />@endif{{ $slot }}
    </a>
@else
    <button type="button" {{ $attributes->class(['chip']) }} aria-pressed="{{ $selected ? 'true' : 'false' }}">
        @if ($icon)<x-ui.icon :name="$icon" size="16" />@endif{{ $slot }}
    </button>
@endif
