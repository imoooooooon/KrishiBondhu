{{--
    F11: the price entry fields. No contributor or version inputs: the server sets who recorded the price.
    $markets: id => label from Market::options(). Works without JavaScript: an estimate simply ignores the date and
    time-of-day fields (the server sets tomorrow), and the hints say so.
--}}
@php
    $entryKind = (string) old('entry_kind', 'actual');
    $priceType = (string) old('price_type', 'retail');
    $session = (string) old('price_session', 'morning');
    $radioError = fn (string $key) => $errors->first($key);
@endphp

@if ($errors->any())
    <x-ui.alert tone="danger" role="alert" tabindex="-1"
        :title="trans_choice('Please fix :count problem.|Please fix :count problems.', count($errors->all()), ['count' => count($errors->all())])">
        <ul class="stack stack-sm">
            @foreach ($errors->getMessages() as $key => $messages)
                @foreach ($messages as $message)
                    <li><a href="#field-{{ $key }}">{{ $message }}</a></li>
                @endforeach
            @endforeach
        </ul>
    </x-ui.alert>
@endif

{{-- What is being recorded: a price seen at the market, or an estimate for tomorrow. --}}
<fieldset class="fieldset" id="field-entry_kind" tabindex="-1">
    <legend class="field__label">{{ __('What are you recording?') }}</legend>
    <div class="check-grid">
        <x-ui.checkbox type="radio" name="entry_kind" value="actual" :label="__('A price I saw at the market')" :checked="$entryKind === 'actual'" />
        <x-ui.checkbox type="radio" name="entry_kind" value="estimate" :label="__('My estimate for tomorrow')" :checked="$entryKind === 'estimate'" />
    </div>
    @if ($radioError('entry_kind'))
        <p class="field__error"><x-ui.icon name="circle-alert" size="16" />{{ $radioError('entry_kind') }}</p>
    @endif
</fieldset>

<x-ui.select name="market_id" :label="__('Market')" :options="$markets" :placeholder="__('Choose a market')" required
    :hint="__('The market where you saw this price.')" />

<div class="grid-2">
    <x-ui.select name="item_code" :label="__('Product')" :options="\App\Models\MarketPrice::itemOptions()" :placeholder="__('Choose a product')" required
        :hint="__('Crops, vegetables, fish, meat, poultry, eggs, milk or oil. The unit is shown next to each product.')" />

    <fieldset class="fieldset" id="field-price_type" tabindex="-1">
        <legend class="field__label">{{ __('Price type') }}</legend>
        <div class="check-grid">
            @foreach (\App\Models\MarketPrice::typeLabels() as $code => $label)
                <x-ui.checkbox type="radio" name="price_type" :value="$code" :label="$label" :checked="$priceType === $code" />
            @endforeach
        </div>
        @if ($radioError('price_type'))
            <p class="field__error"><x-ui.icon name="circle-alert" size="16" />{{ $radioError('price_type') }}</p>
        @endif
    </fieldset>
</div>

<x-ui.field name="unit_price" :label="__('Price per unit')" prefix="৳" inputmode="decimal" autocomplete="off" required
    :hint="__('The price for ONE unit of the product (one kg, one litre or one dozen, as shown next to it), like 35 or 35.50.')" />

<div class="grid-2">
    <x-ui.field name="price_date" type="date" :label="__('Price date')" :value="\App\Models\MarketPrice::today()"
        :min="\App\Models\MarketPrice::earliestDate()" :max="\App\Models\MarketPrice::today()"
        :hint="__('For a price you saw: the day it applied, not a future date. An estimate is always for tomorrow (:date).', ['date' => \App\Models\CropListing::localiseDigits(\App\Models\MarketPrice::tomorrow())])" />

    <fieldset class="fieldset" id="field-price_session" tabindex="-1">
        <legend class="field__label">{{ __('Time of day') }}</legend>
        <p class="field__hint">{{ __('Only for vegetables and fish, whose price changes during the day. Other products have one price per day, and an estimate is for the whole day.') }}</p>
        <div class="check-grid">
            @foreach (\App\Models\MarketPrice::SESSIONS as $code)
                <x-ui.checkbox type="radio" name="price_session" :value="$code" :label="\App\Models\MarketPrice::sessionLabels()[$code]" :checked="$session === $code" />
            @endforeach
        </div>
        @if ($radioError('price_session'))
            <p class="field__error"><x-ui.icon name="circle-alert" size="16" />{{ $radioError('price_session') }}</p>
        @endif
    </fieldset>
</div>

<x-ui.textarea name="note" :label="__('Note').' ('.__('optional').')'" rows="2" maxlength="200"
    :hint="__('For example: grade A, new harvest, fish size 1 kg each, live weight.')" />
