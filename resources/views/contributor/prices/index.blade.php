{{--
    "My market prices" (route contributor.prices.index, market price contributor role). F11.
    Controller: Contributor\MarketPriceController@index. Only this contributor's own entries, latest change first.
    Estimates for tomorrow carry a badge, so they are never mistaken for a price that was seen.
    Entries can be corrected or withdrawn for MarketPrice::CORRECTION_DAYS days; after that they are locked history.
    Withdrawn entries are hidden here (soft delete) but stay in the database.
    The table scrolls sideways on narrow phones (.table-wrap) instead of widening the page.
--}}
<x-layouts.app :title="__('My market prices')">
    <div class="container">
        <x-ui.page-header :title="__('My market prices')" :subtitle="__('Prices you recorded. They are kept as history and are never deleted.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" icon="plus" :href="route('contributor.prices.create')">{{ __('Record a price') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($prices->isEmpty())
            <x-ui.empty-state icon="trending-up" :title="__('You have not recorded any prices')" :description="__('Record the first price you saw at a market.')">
                <x-ui.button size="md" icon="plus" :href="route('contributor.prices.create')">{{ __('Record a price') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="table">
                    <caption class="visually-hidden">{{ __('Prices you recorded') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Price date') }}</th>
                            <th scope="col">{{ __('Time of day') }}</th>
                            <th scope="col">{{ __('Product') }}</th>
                            <th scope="col">{{ __('Market') }}</th>
                            <th scope="col">{{ __('Price type') }}</th>
                            <th scope="col" class="num">{{ __('Price') }}</th>
                            <th scope="col">{{ __('Note') }}</th>
                            <th scope="col"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prices as $price)
                            <tr>
                                <td class="t-num">{{ $price->priceDateForDisplay() }}</td>
                                <td>
                                    @if ($price->is_estimate)
                                        <x-ui.badge tone="highlight" icon="clock">{{ __('Estimate for this day') }}</x-ui.badge>
                                    @else
                                        {{ $price->sessionLabel() }}
                                    @endif
                                </td>
                                <td>{{ $price->itemName() }}</td>
                                <td>{{ $price->market->label() }}</td>
                                <td>{{ $price->typeLabel() }}</td>
                                <td class="num">
                                    <x-ui.price :amount="$price->unit_price" :decimals="2" :unit="$price->unitLabel()" />
                                    @if ($price->isCorrected())<span class="t-caption t-muted"><br>{{ __('Corrected') }}</span>@endif
                                </td>
                                <td>{{ $price->note }}</td>
                                <td>
                                    @if ($price->canStillChange())
                                        <x-ui.button size="sm" variant="tertiary" icon="pencil" :href="route('contributor.prices.edit', $price)">{{ __('Edit') }}<span class="visually-hidden"> {{ $price->itemName() }}, {{ $price->priceDateForDisplay() }}</span></x-ui.button>
                                    @else
                                        <span class="t-caption t-muted">{{ __('Locked (history)') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $prices->links('partials.pagination') }}
        @endif
    </div>
</x-layouts.app>
