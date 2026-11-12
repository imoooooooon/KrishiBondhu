{{--
    Top of every app page: optional breadcrumbs, exactly one h1, a supporting line, actions.
    <x-ui.page-header title="My crop listings" subtitle="Manage what you are selling.">
        <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">Dashboard</a></x-slot:breadcrumbs>
        <x-slot:actions><x-ui.button :href="route('farmer.listings.create')" icon="plus">New listing</x-ui.button></x-slot:actions>
    </x-ui.page-header>
--}}
@props(['title', 'subtitle' => null, 'eyebrow' => null])
<header {{ $attributes->class(['page-header']) }}>
    <div class="page-header__text">
        @isset($breadcrumbs)
            <nav class="breadcrumbs" aria-label="{{ __('Breadcrumb') }}">
                {{ $breadcrumbs }}
                <x-ui.icon name="chevron-right" size="14" />
                <span aria-current="page">{{ $title }}</span>
            </nav>
        @endisset
        @if ($eyebrow)<p class="eyebrow t-overline">{{ $eyebrow }}</p>@endif
        <h1 class="t-title-1">{{ $title }}</h1>
        @if ($subtitle)<p class="t-muted">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="cluster">{{ $actions }}</div>@endisset
</header>
