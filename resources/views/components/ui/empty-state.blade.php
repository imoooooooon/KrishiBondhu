{{--
    <x-ui.empty-state icon="sprout" title="No listings yet" description="Add your first crop so buyers can find it.">
        <x-ui.button size="md" :href="route('farmer.listings.create')">Add a listing</x-ui.button>
    </x-ui.empty-state>
--}}
@props(['title', 'description' => null, 'icon' => 'sprout'])
<div {{ $attributes->class(['empty-state']) }}>
    <span class="empty-state__icon"><x-ui.icon :name="$icon" size="28" /></span>
    <h2 class="t-title-3">{{ $title }}</h2>
    @if ($description)<p>{{ $description }}</p>@endif
    @if ($slot->isNotEmpty())<div class="cluster mt-2">{{ $slot }}</div>@endif
</div>
