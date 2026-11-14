{{--
    F11: the price entry fields. No contributor or version inputs: the server sets who recorded the price.
    $markets: id => label from Market::options(). Works without JavaScript.
--}}
@php
    $priceType = (string) old('price_type', 'retail');
    $typeError = $errors->first('price_type');
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

<x-ui.select name="market_id" :label="__('Market')" :options="$markets" :placeholder="__('Choose a market')" required
    :hint="__('The market where you saw this price.')" />

<div class="grid-2">
    <x-ui.select name="crop_code" :label="__('Crop')" :options="\App\Models\MarketPrice::cropLabels()" :placeholder="__('Choose a crop')" required />

    <fieldset class="fieldset" id="field-price_type" tabindex="-1" @if ($typeError) aria-describedby="field-price_type-error" @endif>
        <legend class="field__label">{{ __('Price type') }}</legend>
        <div class="check-grid">
            @foreach (\App\Models\MarketPrice::typeLabels() as $code => $label)
                <x-ui.checkbox type="radio" name="price_type" :value="$code" :label="$label" :checked="$priceType === $code" />
            @endforeach
        </div>
        @if ($typeError)
            <p class="field__error" id="field-price_type-error"><x-ui.icon name="circle-alert" size="16" />{{ $typeError }}</p>
        @endif
    </fieldset>
</div>

<div class="grid-2">
    <x-ui.field name="price_per_kg" :label="__('Price per kg')" prefix="৳" inputmode="decimal" autocomplete="off" required
        :hint="__('The price for ONE kilogram, like 35 or 35.50.')" />
    <x-ui.field name="price_date" type="date" :label="__('Price date')" :value="\App\Models\MarketPrice::today()"
        :min="\App\Models\MarketPrice::earliestDate()" :max="\App\Models\MarketPrice::today()" required
        :hint="__('The day this price applied. Not a future date.')" />
</div>

<x-ui.textarea name="note" :label="__('Note').' ('.__('optional').')'" rows="2" maxlength="200"
    :hint="__('For example: morning price, grade A, new harvest.')" />
