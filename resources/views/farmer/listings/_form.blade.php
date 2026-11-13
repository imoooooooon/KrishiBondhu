{{--
    F1: fields shared by "Add crop listing" and "Edit listing". $listing is null when adding.
    Four sections (spec §8.2). No farmer_id, status or version inputs here: the server sets the owner,
    publishing has its own buttons, and the edit page adds the hidden version itself.
    Works without JavaScript; public/js/crop-listings.js only adds the live summary and photo previews.
--}}
@php
    $listing = $listing ?? null;
    $unitCode = (string) old('unit_code', $listing?->unit_code ?? 'kg');
    $unitShort = \App\Models\CropListing::unitShortLabel($unitCode);
    $selectedDelivery = $listing?->deliveryMethods() ?? [];
    $existingPhotos = $listing?->photos ?? collect();
    $maxNew = \App\Models\CropListing::MAX_PHOTOS;
    $quantity = old('quantity', $listing ? \App\Models\CropListing::trimDecimal((string) $listing->quantity) : null);
    $price = old('price_per_unit', $listing ? \App\Models\CropListing::trimDecimal((string) $listing->price_per_unit) : null);

    // Error summary: each message links to its field (W3C WAI error-summary pattern).
    $fieldFor = fn (string $key) => match (explode('.', $key)[0]) {
        'photos', 'remove_photo_ids' => 'field-photos',
        'delivery_options' => 'field-delivery_options',
        'version', 'submission_token', 'operation' => 'listing-form',
        default => 'field-'.explode('.', $key)[0],
    };
    $photoErrors = collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, 'photos') || $key === 'remove_photo_ids')->flatten();
@endphp

@if ($errors->any())
    <x-ui.alert tone="danger" role="alert" tabindex="-1" data-error-summary
        :title="trans_choice('Please fix :count problem.|Please fix :count problems.', count($errors->all()), ['count' => count($errors->all())])">
        <ul class="listing-error-list">
            @foreach ($errors->getMessages() as $key => $messages)
                @foreach ($messages as $message)
                    <li><a href="#{{ $fieldFor($key) }}">{{ $message }}</a></li>
                @endforeach
            @endforeach
        </ul>
    </x-ui.alert>
    <x-ui.alert tone="info">{{ __('Please select your new photos again. Your text is still here.') }}</x-ui.alert>
@endif

{{-- 1. Crop details --}}
<section class="listing-form__section" aria-labelledby="section-crop">
    <h2 class="t-title-2" id="section-crop">{{ __('1. Crop details') }}</h2>

    <x-ui.field name="crop_name" :label="__('Crop name')" :value="$listing?->crop_name" maxlength="100" required
        :hint="__('Crop and variety, for example: Potato — Diamant')" />

    <x-ui.textarea name="quality_description" :label="__('Quality')" :value="$listing?->quality_description" rows="3" maxlength="500" required
        :hint="__('Describe the crop’s size, sorting and condition, for example: medium size, sorted, no rot, dry.')" />

    <x-ui.field name="harvest_date" type="date" :label="__('Harvest date')" :value="$listing?->harvest_date?->toDateString()"
        :max="\App\Models\CropListing::today()" required :hint="__('The day it was harvested. It cannot be a future date. Format: YYYY-MM-DD.')" />
</section>

{{-- 2. Quantity and price: the same unit for both, so a price per kg can never be mistaken for a total. --}}
<section class="listing-form__section" aria-labelledby="section-price">
    <h2 class="t-title-2" id="section-price">{{ __('2. Quantity and price') }}</h2>

    <div class="grid-2 listing-form__pair">
        <x-ui.field name="quantity" :label="__('Quantity')" :value="$quantity" inputmode="decimal" autocomplete="off" required
            :hint="__('A plain number, like 250 or 12.5')" data-listing-quantity />
        <x-ui.select name="unit_code" :label="__('Unit')" :options="\App\Models\CropListing::unitLabels()" :value="$unitCode" required
            data-listing-unit data-initial="{{ $unitCode }}" />
    </div>
    <p class="t-callout listing-unit-note" data-unit-note hidden>{{ __('Unit changed. Check both quantity and price before saving.') }}</p>

    <x-ui.field name="price_per_unit" :label="__('Price per :unit', ['unit' => $unitShort])" :value="$price" prefix="৳" inputmode="decimal" autocomplete="off" required
        :hint="__('Your asking price in taka for ONE unit, not the total. Like 35 or 35.50.')" data-listing-price />

    <p class="listing-summary t-body-strong" aria-live="polite" data-listing-summary
        data-template="{{ __('You are offering :quantity :unit at ৳:price per :unit.') }}"
        data-empty="{{ __('Enter the quantity and price to see a summary.') }}"
        data-units='@json(collect(\App\Models\CropListing::UNITS)->mapWithKeys(fn ($u) => [$u => \App\Models\CropListing::unitShortLabel($u)]))'>
        @if (filled($quantity) && filled($price))
            {{ __('You are offering :quantity :unit at ৳:price per :unit.', ['quantity' => $quantity, 'unit' => $unitShort, 'price' => $price]) }}
        @else
            {{ __('Enter the quantity and price to see a summary.') }}
        @endif
    </p>
</section>

{{-- 3. Photos --}}
<section class="listing-form__section" aria-labelledby="section-photos">
    <h2 class="t-title-2" id="section-photos">{{ __('3. Photos') }}</h2>

    @if ($existingPhotos->isNotEmpty())
        <fieldset class="fieldset">
            <legend class="field__label">{{ __('Current photos') }}</legend>
            <p class="field__hint">{{ __('The first photo you keep is the cover. New photos are added after the ones you keep.') }}</p>
            <ul class="listing-photo-grid">
                @foreach ($existingPhotos as $photo)
                    <li class="listing-photo-grid__item">
                        <img src="{{ $photo->url('thumb') }}" width="{{ $photo->thumbnail_width }}" height="{{ $photo->thumbnail_height }}"
                            alt="{{ __('Photo :number', ['number' => $loop->iteration]) }}" loading="lazy">
                        <span class="t-caption">{{ $loop->first ? __('Photo :number (cover)', ['number' => $loop->iteration]) : __('Photo :number', ['number' => $loop->iteration]) }}</span>
                        <x-ui.checkbox name="remove_photo_ids[]" :value="$photo->id" :label="__('Remove this photo')" />
                    </li>
                @endforeach
            </ul>
        </fieldset>
    @endif

    <div class="field">
        <label class="field__label" for="field-photos">{{ $existingPhotos->isEmpty() ? __('Crop photos') : __('Add photos') }}
            @if ($existingPhotos->isNotEmpty())<span class="field__optional">({{ __('optional') }})</span>@endif
        </label>
        <input id="field-photos" name="photos[]" type="file" class="input listing-file" multiple accept="image/jpeg,image/png"
            @if ($existingPhotos->isEmpty()) required @endif
            aria-describedby="field-photos-hint @if ($photoErrors->isNotEmpty()) field-photos-error @endif"
            @if ($photoErrors->isNotEmpty()) aria-invalid="true" @endif
            data-photo-input data-max="{{ $maxNew }}" data-existing="{{ $existingPhotos->count() }}">
        <p class="field__hint" id="field-photos-hint">
            {{ __('1 to :max photos in total, JPG or PNG, up to 5 MB each. The first photo is the cover.', ['max' => $maxNew]) }}
            {{ __('Show the crop only: no documents, faces or house addresses.') }}
        </p>
        @if ($photoErrors->isNotEmpty())
            <div class="field__error" id="field-photos-error">
                <x-ui.icon name="circle-alert" size="16" />
                <ul class="listing-error-list">@foreach ($photoErrors as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif
        <p class="t-callout t-muted" data-photo-status aria-live="polite"></p>
        <ul class="listing-photo-grid" data-photo-previews hidden></ul>
    </div>
</section>

{{-- 4. Location and delivery --}}
<section class="listing-form__section" aria-labelledby="section-delivery">
    <h2 class="t-title-2" id="section-delivery">{{ __('4. Location and delivery') }}</h2>

    <x-ui.field name="public_area" :label="__('Area')" :value="$listing?->public_area" maxlength="120" required
        :hint="__('Upazila and district, like Shibganj, Bogura. Not your house address: anyone can see it.')" />

    @php $deliveryError = $errors->first('delivery_options') ?: $errors->first('delivery_options.*'); @endphp
    <fieldset class="fieldset" id="field-delivery_options" tabindex="-1" @if ($deliveryError) aria-describedby="field-delivery_options-error" @endif>
        <legend class="field__label">{{ __('Delivery options') }}</legend>
        <p class="field__hint">{{ __('Choose every arrangement you accept. No delivery fee is promised here.') }}</p>
        <div class="check-grid">
            @foreach (\App\Models\CropListing::deliveryLabels() as $code => $label)
                <x-ui.checkbox name="delivery_options[]" :value="$code" :label="$label" :checked="in_array($code, $selectedDelivery, true)" />
            @endforeach
        </div>
        @if ($deliveryError)
            <p class="field__error" id="field-delivery_options-error"><x-ui.icon name="circle-alert" size="16" />{{ $deliveryError }}</p>
        @endif
    </fieldset>

    <x-ui.textarea name="delivery_notes" :label="__('Delivery notes').' ('.__('optional').')'" :value="$listing?->delivery_notes" rows="2" maxlength="300"
        :hint="__('For example: pickup from Shibganj bazaar after 3 pm; I can load the truck.')" />
</section>
