{{--
    "Correct a price" (route contributor.prices.edit, own entry within the correction window). F11.
    Controller: Contributor\MarketPriceController@edit. Two actions, each sending the version this page was opened with:
      - correct the price and/or note (PATCH, reason required, old values logged);
      - withdraw the entry (DELETE after an explicit confirmation, reason required): hidden everywhere, kept in the database.
    The "Withdraw" link reloads this page with ?confirm=withdraw; nothing changes on a GET. No JavaScript needed.
--}}
<x-layouts.app :title="__('Correct a price')" narrow>
    <x-ui.page-header :title="__('Correct a price')" :subtitle="$price->itemName().' — '.$price->market->label()">
        <x-slot:breadcrumbs>
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('contributor.prices.index') }}">{{ __('My market prices') }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <x-ui.card class="stack stack-sm mb-6">
        <h2 class="t-title-3">{{ __('What you recorded') }}</h2>
        <dl class="stack stack-sm">
            <div class="cluster cluster--between"><dt class="t-muted">{{ __('Product') }}</dt><dd>{{ $price->itemName() }}</dd></div>
            <div class="cluster cluster--between"><dt class="t-muted">{{ __('Market') }}</dt><dd>{{ $price->market->label() }}</dd></div>
            <div class="cluster cluster--between"><dt class="t-muted">{{ __('Price type') }}</dt><dd>{{ $price->typeLabel() }}</dd></div>
            <div class="cluster cluster--between">
                <dt class="t-muted">{{ __('Price date') }}</dt>
                <dd class="t-num">{{ $price->priceDateForDisplay() }} · {{ $price->is_estimate ? __('Estimate') : $price->sessionLabel() }}</dd>
            </div>
            <div class="cluster cluster--between">
                <dt class="t-muted">{{ __('Price') }}</dt>
                <dd><x-ui.price :amount="$price->unit_price" :decimals="2" :unit="$price->unitLabel()" /></dd>
            </div>
        </dl>
        <p class="t-caption t-muted">{{ __('Wrong market, product, date or time? Withdraw this entry and record it again.') }}</p>
    </x-ui.card>

    @if ($errors->has('version') || $errors->has('confirm_withdraw') || $errors->has('withdrawal_reason'))
        <x-ui.alert tone="danger" role="alert" class="mb-6" :title="__('This was not done')">
            <ul class="stack stack-sm">
                @foreach ([...$errors->get('version'), ...$errors->get('confirm_withdraw'), ...$errors->get('withdrawal_reason')] as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('contributor.prices.update', $price) }}" class="stack stack-lg" id="correct-form">
        @csrf
        @method('PATCH')
        <input type="hidden" name="version" value="{{ old('version', $price->version) }}">

        <x-ui.field name="unit_price" :label="__('Correct price per :unit', ['unit' => $price->unitLabel()])" prefix="৳" inputmode="decimal" autocomplete="off" required
            :value="\App\Models\CropListing::trimDecimal((string) $price->unit_price)" />
        <x-ui.textarea name="note" :label="__('Note').' ('.__('optional').')'" :value="$price->note" rows="2" maxlength="200" />
        <x-ui.field name="reason" :label="__('Why are you correcting it?')" maxlength="200" required
            :hint="__('For example: I typed 350 instead of 35. The old value is kept in the price history.')" />

        <div class="cluster">
            <x-ui.button type="submit" icon="check">{{ __('Save correction') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('contributor.prices.index')">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>

    @if ($price->revisions->isNotEmpty())
        <section class="stack stack-sm mt-8" aria-labelledby="history-title">
            <h2 class="t-title-3" id="history-title">{{ __('Earlier values') }}</h2>
            <div class="list-group">
                @foreach ($price->revisions as $revision)
                    <x-ui.list-row icon="clock" :title="__('Was ৳ :price', ['price' => \App\Models\CropListing::localiseDigits((string) $revision->old_unit_price)])"
                        :subtitle="$revision->reason.' · '.\App\Models\CropListing::localiseDigits($revision->created_at->timezone(\App\Models\MarketPrice::TIMEZONE)->translatedFormat('j M Y, g:i a'))" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($confirmWithdraw)
        <section class="card stack stack-sm mt-8" id="withdraw-confirmation" aria-labelledby="withdraw-title" tabindex="-1">
            <h2 class="t-title-3" id="withdraw-title">{{ __('Withdraw this price?') }}</h2>
            <p class="t-callout">{{ __('It will disappear from the market prices page and your list. It stays in the database with your reason, and you can record the right price again.') }}</p>
            <form method="POST" action="{{ route('contributor.prices.destroy', $price) }}" class="stack stack-sm">
                @csrf @method('DELETE')
                <input type="hidden" name="confirm_withdraw" value="1">
                <input type="hidden" name="version" value="{{ $price->version }}">
                <x-ui.field name="withdrawal_reason" :label="__('Why are you withdrawing it?')" maxlength="200" required
                    :hint="__('For example: wrong market, or I recorded it twice.')" />
                <div class="cluster">
                    <x-ui.button type="submit" size="md" variant="danger" icon="trash-2">{{ __('Withdraw price') }}</x-ui.button>
                    <x-ui.button size="md" variant="tertiary" :href="route('contributor.prices.edit', $price)">{{ __('Cancel') }}</x-ui.button>
                </div>
            </form>
        </section>
    @else
        <section class="stack stack-sm mt-8" aria-labelledby="withdraw-heading">
            <h2 class="t-title-3" id="withdraw-heading">{{ __('Withdraw this price') }}</h2>
            <p class="t-callout t-muted">{{ __('Use this if the entry is wrong in a way a correction cannot fix. It is kept in the database, not erased.') }}</p>
            <div><x-ui.button size="md" variant="danger" icon="trash-2" :href="route('contributor.prices.edit', [$price, 'confirm' => 'withdraw']).'#withdraw-confirmation'">{{ __('Withdraw price') }}</x-ui.button></div>
        </section>
    @endif
</x-layouts.app>
