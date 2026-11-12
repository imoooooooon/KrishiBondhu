{{--
    Living design-system reference (route: design-system). Spec: docs/design-system.md.
    All data on this page is illustrative sample content, not product data.
--}}
@php
    $scales = [
        'Paddy · brand green' => ['paddy', [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]],
        'Sprout · highlighter' => ['sprout', [50, 100, 200, 300, 400, 500, 700, 800]],
        'Shoot · soft tint' => ['shoot', [50, 100, 200, 300]],
        'Mustard · harvest' => ['mustard', [50, 100, 200, 300, 400, 500, 700, 800]],
        'Soil · earth' => ['soil', [50, 100, 200, 300, 500, 700, 800]],
        'Neutral' => ['neutral', [0, 25, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900]],
    ];
    $roles = [
        ['canvas', 'Page background (60 %)'], ['card', 'Grey elements on the canvas'], ['surface', 'Inputs, sheets, dialogs'],
        ['fg', 'Primary text'], ['fg-muted', 'Secondary text'], ['fg-subtle', 'Tertiary text'],
        ['accent', 'Primary action'], ['accent-bg', 'Selected fill'], ['highlight', 'One highlight per region'],
        ['brand', 'Brand surfaces'], ['harvest-bg', 'Harvest / price tags'], ['earth-bg', 'Soil / cattle tags'],
        ['success-bg', 'Success'], ['info-bg', 'Info'], ['warning-bg', 'Warning'], ['danger-bg', 'Danger'],
    ];
    $type = [
        ['t-display-xl', 'Display XL', 'Website hero only'],
        ['t-display', 'Display', 'Section titles, key figures'],
        ['t-title-1', 'Title 1', 'Page title (one h1)'],
        ['t-title-2', 'Title 2', 'Section headers, sheets'],
        ['t-title-3', 'Title 3', 'Card titles'],
        ['t-body', 'Body', 'Default text'],
        ['t-callout', 'Callout', 'Helper and secondary text'],
        ['t-caption', 'Caption', 'Metadata, timestamps'],
        ['t-overline', 'Overline', 'Section labels'],
    ];
    $icons = ['house', 'wheat', 'sprout', 'leaf', 'tractor', 'cow-head', 'store', 'shopping-cart', 'package', 'truck', 'trending-up', 'chart-line', 'map-pin', 'map', 'stethoscope', 'heart-pulse', 'syringe', 'bug', 'droplets', 'network', 'briefcase', 'users', 'user', 'bell', 'calendar', 'clock', 'search', 'sliders-horizontal', 'mic', 'volume-2', 'languages', 'wifi-off', 'hand-coins', 'wallet', 'credit-card', 'receipt', 'scale', 'shield-check', 'badge-check', 'circle-check', 'triangle-alert', 'circle-alert', 'info', 'plus', 'pencil', 'trash-2', 'upload', 'image', 'flag', 'message-square-warning', 'sun', 'moon'];
@endphp
<x-layouts.app title="Design system" description="Krishi Bondhu design system: tokens, typography and components.">
    <section class="section section--canvas-alt" style="padding-block: var(--space-16);">
        <div class="container">
            <div class="stack" style="max-width: 760px;">
                <p class="eyebrow t-overline"><x-ui.icon name="sprout" size="16" /> Design system v1.0</p>
                <h1 class="t-display-xl">Built for the field, readable in the sun.</h1>
                <p class="t-body t-muted max-prose">The living reference for Krishi Bondhu. Use these tokens and Blade components for every page. Specification: <code>docs/design-system.md</code>. All content on this page is illustrative sample data.</p>
                <div class="cluster">
                    <x-ui.button href="#components" icon-after="arrow-right">Components</x-ui.button>
                    <x-ui.button href="#colour" variant="secondary">Colour</x-ui.button>
                    <button type="button" class="btn btn--secondary" data-theme-toggle aria-pressed="false"><x-ui.icon name="moon" class="theme-icon-moon" /><x-ui.icon name="sun" class="theme-icon-sun" /><span>Toggle theme</span></button>
                </div>
            </div>
        </div>
    </section>

    <div class="container stack" style="--stack-gap: var(--section-space); padding-block: var(--space-16);">

        {{-- Principles --}}
        <section aria-labelledby="ds-principles">
            <x-ui.section-heading eyebrow="Principles" title="Six rules before any pixel" />
            <div class="grid" style="--grid-min: 300px;">
                @foreach ([
                    ['White first', 'Most of every screen is white canvas with grey elements. Colour is spent on purpose.'],
                    ['Paddy green carries trust', 'Primary actions, links, brand surfaces and the farmer\'s money.'],
                    ['Sprout is a highlighter', 'One sprout highlight per region: a key figure, progress, the active state. Never text on white.'],
                    ['Readable in the field', 'Larger type and 48 px controls than a typical app: phones in sunlight, first-time web users.'],
                    ['Bangla is first-class', 'Every component wraps Bangla, never truncates it, with taller line height and no capitals.'],
                    ['Real, never generated', 'Real farm photos or a calm placeholder. No stock people, gradients-for-decoration or emoji icons.'],
                ] as [$heading, $copy])
                    <x-ui.card>
                        <h3 class="t-title-3 mb-2">{{ $heading }}</h3>
                        <p class="t-callout t-muted">{{ $copy }}</p>
                    </x-ui.card>
                @endforeach
            </div>
        </section>

        {{-- Colour --}}
        <section id="colour" aria-labelledby="ds-colour">
            <x-ui.section-heading eyebrow="Colour" title="60 / 30 / 10">60 % white and neutrals, 30 % paddy green with shoot tints, 10 % sprout. Mustard and soil are domain accents for harvest, prices, cattle and soil — fills and tags only.</x-ui.section-heading>
            <div class="stack stack-lg">
                @foreach ($scales as $label => [$scale, $steps])
                    <div>
                        <h3 class="t-title-3 mb-4">{{ $label }}</h3>
                        <div class="grid" style="--grid-min: 88px; --grid-gap: var(--space-3);">
                            @foreach ($steps as $step)
                                <div class="swatch">
                                    <div class="swatch__chip" style="background: var(--{{ $scale }}-{{ $step }});"></div>
                                    <span class="swatch__name">{{ $scale }}-{{ $step }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div>
                    <h3 class="t-title-3 mb-2">Semantic roles</h3>
                    <p class="t-callout t-muted mb-4">Components use these names. They switch automatically in dark mode.</p>
                    <div class="grid" style="--grid-min: 200px; --grid-gap: var(--space-3);">
                        @foreach ($roles as [$role, $use])
                            <div class="swatch">
                                <div class="swatch__chip" style="background: var(--{{ $role }});"></div>
                                <span class="swatch__name">--{{ $role }}</span>
                                <span class="swatch__value">{{ $use }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Typography --}}
        <section aria-labelledby="ds-type">
            <div class="cluster cluster--between mb-6">
                <x-ui.section-heading eyebrow="Typography" title="Inter Tight and Noto Sans Bengali" class="mb-0" />
                <div class="pill-switch" role="group" aria-label="Preview language">
                    <button type="button" class="pill-switch__option" data-preview-lang="en" aria-controls="type-preview" aria-pressed="true" lang="en">EN</button>
                    <button type="button" class="pill-switch__option" data-preview-lang="bn" aria-controls="type-preview" aria-pressed="false" lang="bn">বাং</button>
                </div>
            </div>
            <div id="type-preview" lang="en" class="list-group">
                @foreach ($type as [$class, $name, $use])
                    <div class="list-row" style="align-items: baseline; padding-block: var(--space-4);">
                        <span class="t-caption t-subtle" style="width: 120px; flex: none;">{{ $name }}<br>{{ $use }}</span>
                        <span class="{{ $class }}" style="min-width: 0;"><span lang="en" class="type-en">Fair prices for Rajshahi mangoes</span><span lang="bn" class="type-bn">রাজশাহীর আমের ন্যায্য দাম</span></span>
                    </div>
                @endforeach
            </div>
            <p class="t-caption t-subtle mt-4">Bangla uses +1 px, about 1.2× line height, no letter-spacing and no capitals. Figures use tabular numbers: <x-ui.price :amount="125000" /> · <x-ui.price :amount="42.5" unit="kg" />.</p>
        </section>

        {{-- Components --}}
        <section id="components" aria-labelledby="ds-components">
            <x-ui.section-heading eyebrow="Components" title="Blade components">Use <code>&lt;x-ui.* /&gt;</code>. Each file in <code>resources/views/components/ui/</code> documents its props at the top.</x-ui.section-heading>

            <div class="stack stack-lg">
                <x-ui.card>
                    <x-slot:title>Buttons</x-slot:title>
                    <div class="stack">
                        <div class="cluster">
                            <x-ui.button icon="plus">Primary</x-ui.button>
                            <x-ui.button variant="secondary">Secondary</x-ui.button>
                            <x-ui.button variant="tertiary">Tertiary</x-ui.button>
                            <x-ui.button variant="danger" icon="trash-2">Danger</x-ui.button>
                            <x-ui.button disabled>Disabled</x-ui.button>
                        </div>
                        <div class="cluster">
                            <x-ui.button size="lg">Large 48</x-ui.button>
                            <x-ui.button size="md">Medium 40</x-ui.button>
                            <x-ui.button size="sm">Small 32</x-ui.button>
                            <x-ui.icon-button icon="bell" label="Notifications" :badge="3" />
                            <x-ui.icon-button icon="search" label="Search" />
                        </div>
                    </div>
                </x-ui.card>

                <div class="grid-2">
                    <x-ui.card>
                        <x-slot:title>Form fields</x-slot:title>
                        <form class="stack" onsubmit="return false">
                            <x-ui.field name="ds_crop" label="Crop name" placeholder="e.g. Aman rice (BR-11)" hint="Use the name buyers search for." />
                            <x-ui.field name="ds_qty" label="Quantity" type="number" prefix="kg" value="500" />
                            <x-ui.field name="ds_price" label="Price per kg" type="number" prefix="৳" value="12" error="Price must be at least ৳ 20 for this grade." />
                            <x-ui.select name="ds_district" label="District" :options="['rajshahi' => 'Rajshahi', 'bogura' => 'Bogura', 'dinajpur' => 'Dinajpur']" placeholder="Choose a district" />
                            <x-ui.textarea name="ds_notes" label="Notes" optional hint="Storage, quality, anything a buyer should know." />
                            <div>
                                <x-ui.checkbox name="ds_delivery[]" value="pickup" label="Buyer collects from the farm" checked />
                                <x-ui.checkbox name="ds_delivery[]" value="farmer" label="I can deliver" />
                            </div>
                        </form>
                    </x-ui.card>

                    <div class="stack">
                        <x-ui.card>
                            <x-slot:title>Badges</x-slot:title>
                            <div class="cluster">
                                <x-ui.badge tone="success" icon="circle-check">Verified</x-ui.badge>
                                <x-ui.badge tone="info" icon="clock">In review</x-ui.badge>
                                <x-ui.badge tone="warning" icon="triangle-alert">Action needed</x-ui.badge>
                                <x-ui.badge tone="danger" icon="circle-alert">Rejected</x-ui.badge>
                                <x-ui.badge>Draft</x-ui.badge>
                                <x-ui.badge tone="highlight">New</x-ui.badge>
                                <x-ui.badge tone="harvest" icon="wheat">Harvest</x-ui.badge>
                                <x-ui.badge tone="earth" icon="cow-head">Cattle</x-ui.badge>
                            </div>
                        </x-ui.card>
                        <x-ui.card>
                            <x-slot:title>Chips</x-slot:title>
                            <div class="cluster">
                                <x-ui.chip selected>All crops</x-ui.chip>
                                <x-ui.chip icon="wheat">Rice</x-ui.chip>
                                <x-ui.chip>Vegetables</x-ui.chip>
                                <x-ui.chip icon="map-pin">Near me</x-ui.chip>
                            </div>
                        </x-ui.card>
                        <x-ui.alert tone="success" title="Listing published">Buyers in Rajshahi can now see your Aman rice.</x-ui.alert>
                        <x-ui.alert tone="warning" title="Price below market">Your price is 18 % below today's average in Bogura.</x-ui.alert>
                        <x-ui.alert tone="danger" title="Payment failed">The sandbox payment was cancelled. No money was taken.</x-ui.alert>
                        <x-ui.alert tone="info">Market prices were last updated today at 9:30.</x-ui.alert>
                    </div>
                </div>

                <div>
                    <h3 class="t-title-3 mb-4">Cards and data</h3>
                    <div class="grid" style="--grid-min: 260px;">
                        <x-ui.card tone="brand">
                            <p class="t-caption t-muted">This season's sales</p>
                            <p class="t-display t-num mt-2" style="color: var(--highlight);">৳ 2,48,500</p>
                            <p class="t-callout t-muted mt-2">7 of 10 tonnes sold</p>
                            <x-ui.progress :value="7" :max="10" label="Tonnes sold" class="mt-4" />
                        </x-ui.card>
                        <x-ui.card><x-ui.stat label="Rice, coarse · Bogura" value="৳ 42/kg" change="+3 % this week" /></x-ui.card>
                        <x-ui.card><x-ui.stat label="Potato · Munshiganj" value="৳ 28/kg" change="−5 % this week" trend="down" /></x-ui.card>
                        <x-ui.card tone="harvest"><p class="t-title-3">Harvest tag</p><p class="t-callout mt-2">Mustard surfaces mark harvest and market moments.</p></x-ui.card>
                    </div>
                </div>

                <div class="grid" style="--grid-min: 280px;">
                    <x-ui.media-card href="#components" title="Aman rice (BR-11), 500 kg" badge="Available" badge-tone="success" icon="wheat">
                        <x-slot:meta><span>Rajshahi</span><span>Harvested 2 Nov</span><span>Grade A</span></x-slot:meta>
                        <x-slot:footer><x-ui.price :amount="42" unit="kg" class="t-title-3" /><x-ui.badge tone="harvest">Bulk offers</x-ui.badge></x-slot:footer>
                    </x-ui.media-card>
                    <x-ui.media-card href="#components" title="Dr. Rahima Khatun, DVM" badge="Visits today" badge-tone="info" icon="stethoscope">
                        <x-slot:meta><span>Sherpur, Bogura</span><span>4.2 km away</span></x-slot:meta>
                        <x-slot:footer><span class="t-callout t-muted">Cattle, goats</span><x-ui.button size="sm" variant="secondary">Request visit</x-ui.button></x-slot:footer>
                    </x-ui.media-card>
                    <x-ui.media-card href="#components" title="Paddy harvesting, 6 workers" badge="3 places left" badge-tone="warning" icon="tractor">
                        <x-slot:meta><span>Naogaon</span><span>18–20 Nov</span><span>Meals included</span></x-slot:meta>
                        <x-slot:footer><span><x-ui.price :amount="700" unit="day" /></span><x-ui.progress :value="3" :max="6" label="Workers booked" style="width: 96px;" /></x-slot:footer>
                    </x-ui.media-card>
                </div>

                <div class="grid-2">
                    <div>
                        <h3 class="t-title-3 mb-4">List rows</h3>
                        <div class="list-group">
                            <x-ui.list-row icon="syringe" title="Insemination recorded" subtitle="Lal Bahadur · Bull ID BD-2291 · 3 Nov" href="#components" />
                            <x-ui.list-row icon="calendar" title="Calving expected" subtitle="Moyna · 14 Dec"><x-ui.badge tone="info">32 days</x-ui.badge></x-ui.list-row>
                            <x-ui.list-row icon="bug" title="Leaf blast guidance saved" subtitle="Boro rice · 1 Nov" href="#components" />
                        </div>
                    </div>
                    <div>
                        <h3 class="t-title-3 mb-4">Table</h3>
                        <div class="table-wrap">
                            <table class="table">
                                <thead><tr><th scope="col">Market</th><th scope="col">Crop</th><th scope="col" class="num">Price/kg</th><th scope="col">Updated</th></tr></thead>
                                <tbody>
                                    <tr><td>Bogura Mahasthan</td><td>Rice, coarse</td><td class="num"><x-ui.price :amount="42" /></td><td class="t-muted">Today</td></tr>
                                    <tr><td>Rajshahi Shaheb Bazar</td><td>Rice, coarse</td><td class="num"><x-ui.price :amount="44" /></td><td class="t-muted">Today</td></tr>
                                    <tr><td>Dinajpur Bahadur Bazar</td><td>Rice, coarse</td><td class="num"><x-ui.price :amount="40.5" /></td><td class="t-muted">Yesterday</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <x-ui.card tone="surface">
                    <x-ui.empty-state icon="sprout" title="No listings yet" description="Add your first crop so buyers near you can find it.">
                        <x-ui.button size="md" icon="plus">Add a listing</x-ui.button>
                    </x-ui.empty-state>
                </x-ui.card>

                <x-ui.card>
                    <x-slot:title>Page header</x-slot:title>
                    <div class="card__inset">
                        <x-ui.page-header title="My crop listings" subtitle="Manage what you are selling." class="mb-0">
                            <x-slot:breadcrumbs><a href="#components">Dashboard</a></x-slot:breadcrumbs>
                            <x-slot:actions><x-ui.button size="md" icon="plus">New listing</x-ui.button></x-slot:actions>
                        </x-ui.page-header>
                    </div>
                </x-ui.card>
            </div>
        </section>

        {{-- Icons --}}
        <section aria-labelledby="ds-icons">
            <x-ui.section-heading eyebrow="Iconography" title="Line icons, 1.75 px stroke">Lucide (ISC). Use <code>&lt;x-ui.icon name="sprout" /&gt;</code>. Decorative unless given a label.</x-ui.section-heading>
            <div class="grid" style="--grid-min: 112px; --grid-gap: var(--space-3);">
                @foreach ($icons as $icon)
                    <div class="card text-center" style="padding: var(--space-4); display: grid; justify-items: center; gap: var(--space-2);">
                        <x-ui.icon :name="$icon" size="24" />
                        <code class="t-caption" style="background: none; padding: 0;">{{ $icon }}</code>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Brand, radius, spacing --}}
        <section aria-labelledby="ds-brand">
            <x-ui.section-heading eyebrow="Brand and shape" title="Logo, radius and spacing" />
            <div class="grid" style="--grid-min: 300px;">
                <x-ui.card>
                    <x-slot:title>Logo</x-slot:title>
                    <div class="stack">
                        <x-ui.logo size="48" />
                        <x-ui.logo :wordmark="false" size="32" />
                        <div class="card--brand" style="padding: var(--space-4); border-radius: var(--radius-lg);"><x-ui.logo on-dark /></div>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <x-slot:title>Radius</x-slot:title>
                    <div class="stack stack-sm">
                        @foreach (['xs' => 'Badges', 'sm' => 'Small buttons', 'control' => 'Inputs, buttons', 'lg' => 'List groups, media', 'card' => 'Cards', 'panel' => 'Section panels'] as $radius => $use)
                            <div class="cluster"><span style="width: 44px; height: 28px; background: var(--accent-bg-strong); border-radius: var(--radius-{{ $radius }});"></span><span class="t-callout"><code>--radius-{{ $radius }}</code> {{ $use }}</span></div>
                        @endforeach
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <x-slot:title>Spacing (4 px grid)</x-slot:title>
                    <div class="stack stack-sm">
                        @foreach ([1, 2, 3, 4, 6, 8, 12] as $space)
                            <div class="cluster"><span style="width: var(--space-{{ $space }}); height: 16px; background: var(--accent); border-radius: 2px;"></span><span class="t-callout"><code>--space-{{ $space }}</code></span></div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>
        </section>
    </div>

    <x-slot:head>
        <style>
            #type-preview:lang(en) .type-bn, #type-preview:lang(bn) .type-en { display: none; }
        </style>
    </x-slot:head>
</x-layouts.app>
