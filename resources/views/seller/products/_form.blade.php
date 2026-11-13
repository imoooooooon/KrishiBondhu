{{--
    F25: fields shared by "Add product" and "Edit product". $product is null when adding.
    No seller_id, status or version inputs here: the server sets the owner, publishing has its own buttons,
    and the edit page adds the hidden version itself. Works without JavaScript.
--}}
@php
    $product = $product ?? null;
    $trim = fn ($value) => $value === null ? null : \App\Models\CropListing::trimDecimal((string) $value);
    // After a failed submit the browser's own choice wins; an unticked box sends nothing, so check for old input.
    $bulkChecked = session()->hasOldInput()
        ? (string) old('accepts_bulk_offers') === '1'
        : (bool) $product?->acceptsBulkOffers();

    // Error summary: each message links to its field (W3C WAI error-summary pattern).
    $fieldFor = fn (string $key) => match (true) {
        in_array($key, ['version', 'submission_token', 'operation'], true) => 'product-form',
        str_starts_with($key, 'delivery_options') => 'field-delivery_options',
        default => 'field-'.$key,
    };
@endphp

@if ($errors->any())
    <x-ui.alert tone="danger" role="alert" tabindex="-1"
        :title="trans_choice('Please fix :count problem.|Please fix :count problems.', count($errors->all()), ['count' => count($errors->all())])">
        <ul class="supply-error-list">
            @foreach ($errors->getMessages() as $key => $messages)
                @foreach ($messages as $message)
                    <li><a href="#{{ $fieldFor($key) }}">{{ $message }}</a></li>
                @endforeach
            @endforeach
        </ul>
    </x-ui.alert>
@endif

{{-- 1. Product details --}}
<section class="supply-form__section" aria-labelledby="section-product">
    <h2 class="t-title-2" id="section-product">{{ __('1. Product details') }}</h2>

    <x-ui.field name="name" :label="__('Product name')" :value="$product?->name" maxlength="120" required
        :hint="__('What it is and its size, for example: Urea fertilizer, 50 kg bag')" />

    <x-ui.field name="brand" :label="__('Brand')" :value="$product?->brand" maxlength="80" optional />

    <x-ui.select name="category" :label="__('Category')" :options="\App\Models\AgriculturalProduct::categoryLabels()"
        :value="$product?->category" :placeholder="__('Choose a category')" required />

    <x-ui.textarea name="description" :label="__('Description')" :value="$product?->description" rows="4" maxlength="1000" required
        :hint="__('Pack size, how to use it and how to store it. Buyers read this before ordering.')" />
</section>

{{-- 2. Fixed price and stock: the same unit for both, so a price per bag can never be mistaken for a total. --}}
<section class="supply-form__section" aria-labelledby="section-price">
    <h2 class="t-title-2" id="section-price">{{ __('2. Fixed price and stock') }}</h2>

    <x-ui.select name="unit_code" :label="__('Sold per')" :options="\App\Models\AgriculturalProduct::unitLabels()" :value="$product?->unit_code ?? 'bag'" required
        :hint="__('Price and stock both use this unit. Bags, packets, bottles and pieces are whole numbers.')" />

    <div class="grid-2 supply-form__pair">
        <x-ui.field name="unit_price" :label="__('Fixed price per unit')" :value="$trim($product?->unit_price)" prefix="৳" inputmode="decimal" autocomplete="off" required
            :hint="__('The price in taka for ONE unit, like 1350 or 1350.50. Small purchases always pay this price.')" />
        <x-ui.field name="stock_quantity" :label="__('Stock available')" :value="$trim($product?->stock_quantity)" inputmode="decimal" autocomplete="off" required
            :hint="__('How many units you can sell now. Enter 0 when you are out of stock.')" />
    </div>
</section>

{{-- 3. Bulk offers (optional). The lowest price is private: it is only checked against buyers' offers (F3). --}}
<section class="supply-form__section" aria-labelledby="section-bulk">
    <h2 class="t-title-2" id="section-bulk">{{ __('3. Bulk offers') }} <span class="field__optional">({{ __('optional') }})</span></h2>
    <p class="t-callout t-muted">{{ __('For large orders, buyers can offer a price between your lowest bulk price and your fixed price. Leave this off to sell at the fixed price only.') }}</p>

    <div id="field-accepts_bulk_offers">
        <x-ui.checkbox name="accepts_bulk_offers" value="1" :label="__('Accept bulk offers')" :checked="$bulkChecked" />
    </div>

    <div class="grid-2 supply-form__pair">
        <x-ui.field name="bulk_min_quantity" :label="__('Bulk order from')" :value="$trim($product?->bulk_min_quantity)" inputmode="decimal" autocomplete="off"
            :hint="__('The smallest quantity for a bulk offer, at least 2 units. Buyers can see it.')" />
        <x-ui.field name="bulk_min_unit_price" :label="__('Your lowest bulk price per unit')" :value="$trim($product?->bulk_min_unit_price)" prefix="৳" inputmode="decimal" autocomplete="off"
            :hint="__('Private: buyers never see it. It cannot be higher than the fixed price.')" />
    </div>
    <p class="t-caption t-muted">{{ __('If “Accept bulk offers” is not ticked, these two boxes are ignored and cleared.') }}</p>
</section>

{{-- 4. Area and delivery. Anyone can see these, so the area is approximate (never a house or shop address). --}}
<section class="supply-form__section" aria-labelledby="section-delivery">
    <h2 class="t-title-2" id="section-delivery">{{ __('4. Area and delivery') }}</h2>

    <x-ui.field name="public_area" :label="__('Area')" :value="$product?->public_area" maxlength="120" required
        :hint="__('Upazila and district you sell from, like Shibganj, Bogura. Not your exact address: anyone can see it.')" />

    @php
        $selectedDelivery = $product?->deliveryMethods() ?? [];
        $deliveryError = $errors->first('delivery_options') ?: $errors->first('delivery_options.*');
    @endphp
    <fieldset class="fieldset" id="field-delivery_options" tabindex="-1" @if ($deliveryError) aria-describedby="field-delivery_options-error" @endif>
        <legend class="field__label">{{ __('Delivery options') }}</legend>
        <p class="field__hint">{{ __('Choose every arrangement you accept. No delivery fee is promised here.') }}</p>
        <div class="check-grid">
            @foreach (\App\Models\AgriculturalProduct::deliveryLabels() as $code => $label)
                <x-ui.checkbox name="delivery_options[]" :value="$code" :label="$label" :checked="in_array($code, $selectedDelivery, true)" />
            @endforeach
        </div>
        @if ($deliveryError)
            <p class="field__error" id="field-delivery_options-error"><x-ui.icon name="circle-alert" size="16" />{{ $deliveryError }}</p>
        @endif
    </fieldset>

    <x-ui.textarea name="delivery_notes" :label="__('Delivery notes').' ('.__('optional').')'" :value="$product?->delivery_notes" rows="2" maxlength="300"
        :hint="__('For example: pickup from Shibganj bazaar, 9 am to 6 pm; courier to any district.')" />
</section>

{{-- 5. Photo (optional). One photo; it is re-encoded on the server, so metadata such as GPS location is removed. --}}
<section class="supply-form__section" aria-labelledby="section-photo">
    <h2 class="t-title-2" id="section-photo">{{ __('5. Photo') }} <span class="field__optional">({{ __('optional') }})</span></h2>

    @if ($product?->hasPhoto())
        <div class="supply-photo-current">
            <img src="{{ $product->photoUrl('thumb') }}" alt="{{ __('Current photo') }}" loading="lazy">
            <x-ui.checkbox name="remove_photo" value="1" :label="__('Remove this photo')" />
        </div>
    @endif

    @php $photoError = $errors->first('photo'); @endphp
    <div class="field">
        <label class="field__label" for="field-photo">{{ $product?->hasPhoto() ? __('Replace with a new photo') : __('Product photo') }}</label>
        <input id="field-photo" name="photo" type="file" class="input supply-file" accept="image/jpeg,image/png"
            aria-describedby="field-photo-hint @if ($photoError) field-photo-error @endif" @if ($photoError) aria-invalid="true" @endif>
        <p class="field__hint" id="field-photo-hint">{{ __('JPG or PNG, up to 5 MB. Show the product or its package clearly.') }}</p>
        @if ($photoError)
            <p class="field__error" id="field-photo-error"><x-ui.icon name="circle-alert" size="16" />{{ $photoError }}</p>
        @endif
    </div>
    @if ($errors->any())
        <p class="t-callout t-muted">{{ __('If you chose a new photo, please select it again. Your text is still here.') }}</p>
    @endif
</section>
