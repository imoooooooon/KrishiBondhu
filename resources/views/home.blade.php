{{--
    Landing page (route: home). Basic version: describes what Krishi Bondhu offers (SRS §1.2) and links into each
    area. No statistics, testimonials or prices are shown until real data exists.
    Text is English in the source and translated through lang/bn.json.
--}}
@php
    $areas = [
        ['route' => 'marketplace.index', 'icon' => 'wheat', 'tone' => 'accent', 'title' => __('Crop marketplace'), 'bn' => 'ফসলের বাজার',
            'copy' => __('List your harvest, reach buyers directly, and negotiate bulk prices within your own limits.')],
        ['route' => 'market-prices.index', 'icon' => 'trending-up', 'tone' => 'harvest', 'title' => __('Market prices'), 'bn' => 'বাজারদর',
            'copy' => __('Compare local market prices per kilogram, see past trends, and find markets near you.')],
        ['route' => 'cattle.index', 'icon' => 'cow-head', 'tone' => 'earth', 'title' => __('Cattle records'), 'bn' => 'গবাদিপশুর রেকর্ড',
            'copy' => __('Keep cattle profiles, breeding and family history, with warnings before close-relative breeding.')],
        ['route' => 'health.index', 'icon' => 'stethoscope', 'tone' => 'accent', 'title' => __('Crop and animal health'), 'bn' => 'ফসল ও পশুর স্বাস্থ্য',
            'copy' => __('Get guidance on crop diseases, animal health and fertilizer, and request a visit from a vet.')],
        ['route' => 'jobs.index', 'icon' => 'tractor', 'tone' => 'harvest', 'title' => __('Farm jobs'), 'bn' => 'কৃষি কাজ',
            'copy' => __('Post farm work, book individual workers or groups, and agree wages clearly.')],
        ['route' => 'shop.index', 'icon' => 'store', 'tone' => 'earth', 'title' => __('Farm supplies'), 'bn' => 'কৃষি উপকরণ',
            'copy' => __('Buy fertilizer, pesticide and tools from agricultural sellers, in small or bulk orders.')],
    ];
    $steps = [
        ['icon' => 'user', 'title' => __('Join with one account'), 'copy' => __('Farmer, buyer, worker or seller: add the roles you need to the same account.')],
        ['icon' => 'sprout', 'title' => __('List or search'), 'copy' => __('Post a crop, a job or a cow record, or search what others offer near you.')],
        ['icon' => 'hand-coins', 'title' => __('Agree and get paid'), 'copy' => __('Negotiate within your limits, confirm the order and pay through the platform.')],
    ];
    $isBangla = app()->getLocale() === 'bn';
@endphp
<x-layouts.app :title="null" :description="__('Krishi Bondhu helps farmers in Bangladesh sell crops at fair prices and manage their farming needs, in Bangla and English.')">
    {{-- Hero --}}
    <section class="hero">
        <div class="container hero__grid">
            <div class="stack hero__copy">
                <p class="eyebrow t-overline"><x-ui.icon name="sprout" size="16" /> <span lang="bn">কৃষকের পাশে, প্রতিদিন</span></p>
                <h1 class="t-display-xl">{{ __('Sell your harvest at a fair price.') }}</h1>
                <p class="t-body t-muted max-prose">{{ __('Krishi Bondhu connects farmers in Bangladesh with buyers, workers and veterinary doctors, with market prices, cattle records and farming guidance in one place, in Bangla and English.') }}</p>
                <div class="cluster mt-2">
                    <x-ui.button :href="route('marketplace.index')" icon-after="arrow-right">{{ __('Browse crops') }}</x-ui.button>
                    <x-ui.button :href="route('market-prices.index')" variant="secondary" icon="trending-up">{{ __("Today's market prices") }}</x-ui.button>
                </div>
                @guest
                    <p class="t-callout t-muted">{{ __('New here?') }} <a href="{{ route('register') }}">{{ __('Create a free account') }}</a></p>
                @endguest
            </div>
            <x-ui.card tone="brand" class="hero__panel">
                <p class="t-overline t-muted">{{ __('What you can do') }}</p>
                <ul class="hero__list" role="list">
                    <li><x-ui.icon name="circle-check" /> {{ __('Sell crops directly to buyers') }}</li>
                    <li><x-ui.icon name="circle-check" /> {{ __('Check prices before you go to market') }}</li>
                    <li><x-ui.icon name="circle-check" /> {{ __("Keep your cattle's records safe") }}</li>
                    <li><x-ui.icon name="circle-check" /> {{ __('Find workers and vets nearby') }}</li>
                </ul>
                <p class="hero__panel-bn" lang="bn">বাংলা লেখা ও কণ্ঠস্বরে ব্যবহার করুন</p>
            </x-ui.card>
        </div>
    </section>

    {{-- Service areas --}}
    <section class="section">
        <div class="container">
            <x-ui.section-heading :eyebrow="__('Everything in one place')" :title="__('Built around a farmer\'s year')">{{ __('From planting to selling, the tools that farmers, buyers and workers use every day.') }}</x-ui.section-heading>
            <div class="grid" style="--grid-min: 300px;">
                @foreach ($areas as $area)
                    <x-ui.card :href="route($area['route'])" class="area-card" data-reveal>
                        <span class="area-card__icon area-card__icon--{{ $area['tone'] }}"><x-ui.icon :name="$area['icon']" size="24" /></span>
                        <h3 class="t-title-3">{{ $area['title'] }}</h3>
                        @unless ($isBangla)<p class="t-caption t-subtle" lang="bn">{{ $area['bn'] }}</p>@endunless
                        <p class="t-callout t-muted">{{ $area['copy'] }}</p>
                        <span class="area-card__link">{{ __('Open') }} <x-ui.icon name="arrow-right" size="16" /></span>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="section section--canvas-alt">
        <div class="container">
            <x-ui.section-heading :eyebrow="__('How it works')" :title="__('Three steps to your first sale')" center />
            <ol class="steps" role="list">
                @foreach ($steps as $index => $step)
                    <li class="card card--surface steps__item" data-reveal>
                        <span class="steps__number t-num">{{ $index + 1 }}</span>
                        <span class="list-row__icon"><x-ui.icon :name="$step['icon']" /></span>
                        <h3 class="t-title-3">{{ $step['title'] }}</h3>
                        <p class="t-callout t-muted">{{ $step['copy'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Call to action --}}
    <section class="section">
        <div class="container">
            <div class="panel cta" data-reveal>
                <div class="stack">
                    <h2 class="t-display">{{ __('Ready to sell your next harvest?') }}</h2>
                    <p class="t-muted max-prose">{{ __('Join Krishi Bondhu as a farmer, buyer, worker, vet or seller. One account works for every role.') }}</p>
                </div>
                <div class="cluster">
                    @auth
                        <x-ui.button :href="route('dashboard')" icon="layout-dashboard">{{ __('Go to your dashboard') }}</x-ui.button>
                    @else
                        <x-ui.button :href="route('register')">{{ __('Join Krishi Bondhu') }}</x-ui.button>
                        <x-ui.button :href="route('login')" variant="secondary">{{ __('Log in') }}</x-ui.button>
                    @endauth
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
