{{--
    Public market prices (route market-prices.index). F11. Controller: MarketPriceController@index.
    One row per market + product + price type: today's latest price, yesterday's price, the change, tomorrow's
    estimate (clearly marked) and the last update, so an old price is never mistaken for today's.
    The numbers come from MarketPriceService; this view only formats them. Contributors' names are never shown.
    Filters are a plain GET form and links, so they work without JavaScript and can be shared.
--}}
@php
    $groupLink = fn (?string $group) => route('market-prices.index', array_filter([
        'group' => $group,
        'market' => $filters['market'] ?? null,
        'type' => $filters['type'] ?? null,
    ]));
@endphp
<x-layouts.app :title="__('Market prices')" :description="__('Today’s local market prices for crops, vegetables, fish, meat, eggs, milk and oil, with yesterday’s price and tomorrow’s estimate.')">
    <div class="container">
        <x-ui.page-header :title="__('Market prices')"
            :subtitle="__('Local prices entered by market price contributors: today, yesterday and an estimate for tomorrow. Vegetable and fish prices can change during the day.')">
            @if ($isContributor)
                <x-slot:actions>
                    <x-ui.button size="md" icon="plus" :href="route('contributor.prices.create')">{{ __('Record a price') }}</x-ui.button>
                </x-slot:actions>
            @endif
        </x-ui.page-header>

        <nav class="cluster mb-4" aria-label="{{ __('Product groups') }}">
            <x-ui.chip :href="$groupLink(null)" :selected="! isset($filters['group'])">{{ __('All products') }}</x-ui.chip>
            @foreach (\App\Models\MarketPrice::groupLabels() as $code => $label)
                <x-ui.chip :href="$groupLink($code)" :selected="($filters['group'] ?? null) === $code">{{ $label }}</x-ui.chip>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('market-prices.index') }}" class="card stack mb-6" role="search" aria-label="{{ __('Filter market prices') }}">
            @isset($filters['group'])<input type="hidden" name="group" value="{{ $filters['group'] }}">@endisset
            <div class="grid">
                <x-ui.select name="item" :label="__('Product')" :value="$filters['item'] ?? ''" :options="['' => __('All products')] + \App\Models\MarketPrice::itemOptions()" />
                <x-ui.select name="market" :label="__('Market')" :value="$filters['market'] ?? ''" :options="['' => __('All markets')] + $markets" />
                <x-ui.select name="type" :label="__('Price type')" :value="$filters['type'] ?? ''" :options="['' => __('Retail and wholesale')] + \App\Models\MarketPrice::typeLabels()" />
            </div>
            <div class="cluster">
                <x-ui.button type="submit" size="md" icon="search">{{ __('Show prices') }}</x-ui.button>
                @if ($filters !== [])
                    <x-ui.button size="md" variant="tertiary" :href="route('market-prices.index')">{{ __('Clear filters') }}</x-ui.button>
                @endif
            </div>
        </form>

        @if ($board->isEmpty())
            <x-ui.empty-state icon="trending-up" :title="$filters === [] ? __('No market prices yet') : __('No prices match these filters')"
                :description="$filters === [] ? __('Prices appear here as soon as a market price contributor records them.') : __('Try another product, market or price type.')">
                @if ($filters !== [])
                    <x-ui.button size="md" variant="secondary" :href="route('market-prices.index')">{{ __('Show all prices') }}</x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="table">
                    <caption class="visually-hidden">{{ __('Latest market prices') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Product') }}</th>
                            <th scope="col">{{ __('Market') }}</th>
                            <th scope="col">{{ __('Price type') }}</th>
                            <th scope="col" class="num">{{ __('Today') }}</th>
                            <th scope="col" class="num">{{ __('Yesterday') }}</th>
                            <th scope="col">{{ __('Change') }}</th>
                            <th scope="col" class="num">{{ __('Tomorrow (estimate)') }}</th>
                            <th scope="col">{{ __('Last updated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($board as $row)
                            @php $latest = $row['latest']; @endphp
                            <tr>
                                <th scope="row">
                                    <a href="{{ route('market-prices.index', ['item' => $latest->item_code, 'market' => $latest->market_id]) }}">{{ $latest->itemName() }}</a>
                                </th>
                                <td>{{ $latest->market->label() }}</td>
                                <td>{{ $latest->typeLabel() }}</td>
                                <td class="num">
                                    @if ($row['today'])
                                        <x-ui.price :amount="$row['today']->unit_price" :decimals="2" :unit="$latest->unitLabel()" />
                                        @if (\App\Models\MarketPrice::changesDuringDay($latest->item_code))
                                            <span class="t-caption t-muted"><br>{{ $row['today']->sessionLabel() }}</span>
                                        @endif
                                    @else
                                        <span class="t-muted">{{ __('No price yet today') }}</span>
                                    @endif
                                </td>
                                <td class="num">
                                    @if ($row['yesterday'])
                                        <x-ui.price :amount="$row['yesterday']->unit_price" :decimals="2" :unit="$latest->unitLabel()" />
                                    @else
                                        <span class="t-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['change'] !== null)
                                        {{-- Direction as text (sign) and icon, never colour alone. --}}
                                        <x-ui.badge tone="neutral" :icon="['up' => 'trending-up', 'down' => 'chevron-down', 'same' => 'minus'][$row['direction']]">
                                            {{ \App\Models\CropListing::localiseDigits('৳ '.$row['change'].' ('.$row['percent'].'%)') }}
                                        </x-ui.badge>
                                    @else
                                        <span class="t-muted">—</span>
                                    @endif
                                </td>
                                <td class="num">
                                    @if ($row['estimate'])
                                        <x-ui.price :amount="$row['estimate']->unit_price" :decimals="2" :unit="$latest->unitLabel()" />
                                        <span class="t-caption t-muted"><br>{{ __('Estimate, not a guarantee') }}</span>
                                    @else
                                        <span class="t-muted">—</span>
                                    @endif
                                </td>
                                <td class="t-num">
                                    {{ $latest->priceDateForDisplay() }}
                                    @if (\App\Models\MarketPrice::changesDuringDay($latest->item_code))
                                        <span class="t-caption t-muted"><br>{{ $latest->sessionLabel() }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $board->links('partials.pagination') }}
        @endif

        @if ($recent !== null && $recentMarket)
            <section class="stack mt-8" aria-labelledby="recent-title">
                <x-ui.section-heading :title="__('Recent entries: :item at :market', ['item' => \App\Models\MarketPrice::itemNames()[$filters['item']], 'market' => $recentMarket->displayName()])" level="2">
                    {{ __('The latest :count entries for this product at this market. Every entry is kept as price history.', ['count' => \App\Models\CropListing::localiseDigits((string) \App\Services\MarketPriceService::RECENT_LIMIT)]) }}
                </x-ui.section-heading>
                @if ($recent->isEmpty())
                    <p class="t-muted">{{ __('No prices recorded for this product at this market yet.') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <caption class="visually-hidden">{{ __('Recent entries') }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Price date') }}</th>
                                    <th scope="col">{{ __('Time of day') }}</th>
                                    <th scope="col">{{ __('Price type') }}</th>
                                    <th scope="col" class="num">{{ __('Price') }}</th>
                                    <th scope="col">{{ __('Note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent as $entry)
                                    <tr>
                                        <td class="t-num">{{ $entry->priceDateForDisplay() }}</td>
                                        <td>
                                            @if ($entry->is_estimate)
                                                <x-ui.badge tone="highlight" icon="clock">{{ __('Estimate') }}</x-ui.badge>
                                            @else
                                                {{ $entry->sessionLabel() }}
                                            @endif
                                        </td>
                                        <td>{{ $entry->typeLabel() }}</td>
                                        <td class="num"><x-ui.price :amount="$entry->unit_price" :decimals="2" :unit="$entry->unitLabel()" /></td>
                                        <td>{{ $entry->note }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif

        <p class="t-caption t-muted mt-8">
            {{ __('Prices are entered by people at the markets and may differ from what you are offered. Comparing markets and price trends will come later.') }}
        </p>
    </div>
</x-layouts.app>
