{{-- Global header. Navigation items follow docs/IA.md §3 (primary navigation). --}}
@php
    $navigation = [
        ['route' => 'marketplace.index', 'match' => ['marketplace.*', 'bulk-requests.*', 'negotiations.*'], 'label' => __('Marketplace'), 'icon' => 'wheat'],
        ['route' => 'market-prices.index', 'match' => ['market-prices.*', 'markets.*'], 'label' => __('Market prices'), 'icon' => 'trending-up'],
        ['route' => 'shop.index', 'match' => ['shop.*'], 'label' => __('Farm supplies'), 'icon' => 'store'],
        ['route' => 'cattle.index', 'match' => ['cattle.*', 'inseminations.*', 'pregnancies.*', 'breeding-check.*'], 'label' => __('Cattle'), 'icon' => 'cow-head'],
        ['route' => 'health.index', 'match' => ['health.*', 'guidance.*', 'vets.*', 'treatments.*', 'visit-requests.*'], 'label' => __('Health'), 'icon' => 'stethoscope'],
        ['route' => 'jobs.index', 'match' => ['jobs.*', 'bookings.*'], 'label' => __('Farm jobs'), 'icon' => 'tractor'],
    ];
    $locale = app()->getLocale();
@endphp
<header class="site-header">
    <div class="container site-header__bar">
        <a href="{{ route('home') }}" class="site-header__brand" aria-label="{{ __('Krishi Bondhu home') }}">
            <x-ui.logo />
        </a>

        <nav class="site-nav" id="site-nav" aria-label="{{ __('Main') }}">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}" class="site-nav__link" @if (request()->routeIs(...$item['match'])) aria-current="page" @endif>
                    <x-ui.icon :name="$item['icon']" size="18" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="site-header__actions">
            <form method="POST" action="{{ route('preferences.locale') }}" class="pill-switch hide-sm" aria-label="{{ __('Language') }}">
                @csrf
                <button class="pill-switch__option" name="locale" value="en" lang="en" aria-pressed="{{ $locale === 'en' ? 'true' : 'false' }}">EN</button>
                <button class="pill-switch__option" name="locale" value="bn" lang="bn" aria-pressed="{{ $locale === 'bn' ? 'true' : 'false' }}">বাং</button>
            </form>
            <button type="button" class="icon-btn hide-sm" data-theme-toggle aria-pressed="false" aria-label="{{ __('Dark theme') }}" title="{{ __('Dark theme') }}">
                <x-ui.icon name="moon" class="theme-icon-moon" /><x-ui.icon name="sun" class="theme-icon-sun" />
            </button>
            <x-ui.icon-button icon="shopping-cart" :label="__('Cart')" :href="route('cart.show')" class="hide-sm" />
            @auth
                <x-ui.icon-button icon="bell" :label="__('Notifications')" :href="route('notifications.index')" />
                <x-ui.button size="md" variant="secondary" :href="route('dashboard')" icon="layout-dashboard">{{ __('Dashboard') }}</x-ui.button>
            @else
                <x-ui.button size="md" variant="tertiary" :href="route('login')" class="hide-sm">{{ __('Log in') }}</x-ui.button>
                <x-ui.button size="md" :href="route('register')">{{ __('Join') }}</x-ui.button>
            @endauth
            <button type="button" class="icon-btn site-header__menu-button" data-menu-toggle aria-expanded="false" aria-controls="site-nav" aria-label="{{ __('Menu') }}">
                <x-ui.icon name="menu" />
            </button>
        </div>

        {{-- Phone: preferences and secondary actions move into the open menu. --}}
        <div class="site-header__mobile-actions">
            <form method="POST" action="{{ route('preferences.locale') }}" class="pill-switch" aria-label="{{ __('Language') }}">
                @csrf
                <button class="pill-switch__option" name="locale" value="en" lang="en" aria-pressed="{{ $locale === 'en' ? 'true' : 'false' }}">EN</button>
                <button class="pill-switch__option" name="locale" value="bn" lang="bn" aria-pressed="{{ $locale === 'bn' ? 'true' : 'false' }}">বাং</button>
            </form>
            <button type="button" class="icon-btn" data-theme-toggle aria-pressed="false" aria-label="{{ __('Dark theme') }}"><x-ui.icon name="moon" class="theme-icon-moon" /><x-ui.icon name="sun" class="theme-icon-sun" /></button>
            <x-ui.icon-button icon="shopping-cart" :label="__('Cart')" :href="route('cart.show')" />
            @guest
                <x-ui.button size="md" variant="secondary" :href="route('login')">{{ __('Log in') }}</x-ui.button>
            @endguest
        </div>
    </div>
</header>
