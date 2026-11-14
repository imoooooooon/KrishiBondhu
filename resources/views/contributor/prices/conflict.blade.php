{{--
    409 Conflict (rendered by App\Exceptions\MarketPriceConflictException). F11.
    The entry changed after this contributor opened the page, so nothing was saved. The latest saved entry and what
    was sent are shown side by side. $price: latest MarketPrice. $submitted: the fields sent (empty for a withdrawal).
--}}
<x-layouts.app :title="__('This price changed')">
    <div class="container">
        <x-ui.page-header :title="__('This price changed after you opened it')">
            <x-slot:breadcrumbs><a href="{{ route('contributor.prices.index') }}">{{ __('My market prices') }}</a></x-slot:breadcrumbs>
        </x-ui.page-header>

        <x-ui.alert tone="warning" role="alert" class="mb-6" :title="__('Your change was not saved.')">
            {{ __('It was changed in another tab or on another phone. Compare the two below, then open the latest page and try again.') }}
        </x-ui.alert>

        <div class="grid-2">
            <x-ui.card class="stack stack-sm">
                <h2 class="t-title-3">{{ __('Latest saved version') }}</h2>
                <p>{{ $price->itemName() }} — {{ $price->market->label() }}</p>
                <p><x-ui.price :amount="$price->unit_price" :decimals="2" :unit="$price->unitLabel()" /></p>
                @if ($price->note)<p class="t-callout">{{ $price->note }}</p>@endif
            </x-ui.card>
            <x-ui.card tone="surface" class="stack stack-sm">
                <h2 class="t-title-3">{{ __('What you sent') }}</h2>
                @if ($submitted === [])
                    <p class="t-callout t-muted">{{ __('You pressed Withdraw on an old copy of the page. No text was sent.') }}</p>
                @else
                    @if (is_scalar($submitted['unit_price'] ?? null))<p>{{ __('Price') }}: ৳ {{ $submitted['unit_price'] }}</p>@endif
                    @if (is_scalar($submitted['note'] ?? null) && $submitted['note'] !== '')<p class="t-callout">{{ $submitted['note'] }}</p>@endif
                    @if (is_scalar($submitted['reason'] ?? null))<p class="t-caption t-muted">{{ __('Reason') }}: {{ $submitted['reason'] }}</p>@endif
                @endif
            </x-ui.card>
        </div>

        <div class="cluster mt-8">
            @if ($price->canStillChange())
                <x-ui.button icon="pencil" :href="route('contributor.prices.edit', $price)">{{ __('Open the latest version') }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" :href="route('contributor.prices.index')">{{ __('Back to my market prices') }}</x-ui.button>
        </div>
    </div>
</x-layouts.app>
