{{--
    F6: fields shared by "Register cattle" and "Edit profile". $cattle is null when registering.
    No KB ID, owner or archive inputs: the server sets those. Works without JavaScript; public/js/cattle.js only
    hides the age inputs that do not apply and previews the photo.
--}}
@php
    $cattle = $cattle ?? null;
    $editing = $cattle !== null;
    $breedKnown = old('breed_known', $editing ? ($cattle->breed_description ? 'yes' : 'no') : 'yes');
    $ageBasis = old('age_basis', $cattle?->age_basis ?? 'exact');
    $ageUpdate = old('age_update', 'keep');
    $estimateYears = $cattle?->age_basis === 'estimated' ? intdiv($cattle->estimated_age_months, 12) : null;
    $estimateMonths = $cattle?->age_basis === 'estimated' ? $cattle->estimated_age_months % 12 : null;
    $today = \App\Services\CattleAge::today();
    $fieldFor = fn (string $key) => match (true) {
        in_array($key, ['version', 'submission_token', 'operation', 'cattle'], true) => 'cattle-form',
        $key === 'sex' => 'field-sex', $key === 'age_basis' => 'field-age_basis', $key === 'breed_known' => 'field-breed_known',
        default => 'field-'.$key,
    };
@endphp

@if ($errors->any())
    <x-ui.alert tone="danger" role="alert" tabindex="-1" data-error-summary
        :title="trans_choice('Please fix :count problem.|Please fix :count problems.', count($errors->all()), ['count' => count($errors->all())])">
        <ul class="cattle-list">
            @foreach ($errors->getMessages() as $key => $messages)
                @foreach ($messages as $message)<li><a href="#{{ $fieldFor($key) }}">{{ $message }}</a></li>@endforeach
            @endforeach
        </ul>
    </x-ui.alert>
    <x-ui.alert tone="info">{{ __('If you chose a new photo, please choose it again. Your text is still here.') }}</x-ui.alert>
@endif

{{-- 1. Identity and photo --}}
<section class="cattle-form__section" aria-labelledby="section-identity">
    <h2 class="t-title-2" id="section-identity">{{ __('1. Identity and photo') }}</h2>
    @unless ($editing)
        <p class="t-callout t-muted">{{ __('A unique KB cattle ID will be created when you save. Your own farm tag and nickname are optional.') }}</p>
    @endunless

    <div class="field">
        @if ($editing)
            <div class="cattle-current-photo">
                <img src="{{ $cattle->photoUrl('thumb') }}" width="{{ $cattle->thumbnail_width }}" height="{{ $cattle->thumbnail_height }}" alt="{{ __('Current photo') }}">
                <span class="t-callout t-muted">{{ __('Current photo. It stays unless you choose a new one.') }}</span>
            </div>
        @endif
        <label class="field__label" for="field-photo">{{ $editing ? __('Replace photo') : __('Photo of the animal') }}
            @if ($editing)<span class="field__optional">({{ __('optional') }})</span>@endif
        </label>
        <input id="field-photo" name="photo" type="file" class="input cattle-file" accept="image/jpeg,image/png" @unless ($editing) required @endunless
            aria-describedby="field-photo-hint @error('photo') field-photo-error @enderror" @error('photo') aria-invalid="true" @enderror data-photo-input>
        <p class="field__hint" id="field-photo-hint">{{ __('One JPG or PNG photo, up to 5 MB. Show the whole animal and any markings, so you can recognise it. Only you can see it.') }}</p>
        @error('photo')<p class="field__error" id="field-photo-error"><x-ui.icon name="circle-alert" size="16" />{{ $message }}</p>@enderror
        <img class="cattle-photo-preview" alt="" data-photo-preview hidden>
    </div>

    <div class="grid-2 cattle-form__pair">
        <x-ui.field name="nickname" :label="__('Nickname')" :value="$cattle?->nickname" maxlength="80" optional :hint="__('For example: Lali')" />
        <x-ui.field name="farm_tag" :label="__('Farm tag')" :value="$cattle?->farm_tag" maxlength="50" optional :hint="__('Your ear tag or number, like T-07')" />
    </div>

    <fieldset class="fieldset" id="field-sex" tabindex="-1" @error('sex') aria-describedby="field-sex-error" @enderror>
        <legend class="field__label">{{ __('Sex') }}</legend>
        <div class="check-grid">
            @foreach (\App\Models\Cattle::sexLabels() as $code => $label)
                <x-ui.checkbox type="radio" name="sex" :value="$code" :label="$label" :checked="($cattle?->sex ?? '') === $code" required />
            @endforeach
        </div>
        @error('sex')<p class="field__error" id="field-sex-error"><x-ui.icon name="circle-alert" size="16" />{{ $message }}</p>@enderror
    </fieldset>

    <fieldset class="fieldset" id="field-breed_known" tabindex="-1">
        <legend class="field__label">{{ __('Breed') }}</legend>
        <div class="check-grid">
            <x-ui.checkbox type="radio" name="breed_known" value="yes" :label="__('I know the breed')" :checked="$breedKnown === 'yes'" data-breed-choice />
            <x-ui.checkbox type="radio" name="breed_known" value="no" :label="__('Not known')" :checked="$breedKnown === 'no'" data-breed-choice />
        </div>
    </fieldset>
    <div data-breed-field>
        <x-ui.field name="breed_description" :label="__('Breed description')" :value="$cattle?->breed_description" maxlength="100"
            :hint="__('As you would describe it, like Red Chittagong, Pabna, or Holstein cross. Ignored if you chose “Not known”.')" />
    </div>
</section>

{{-- 2. Age: exact, estimated or unknown. Only the chosen one is saved; today's age is calculated when shown. --}}
<section class="cattle-form__section" aria-labelledby="section-age">
    <h2 class="t-title-2" id="section-age">{{ __('2. Age') }}</h2>

    @if ($editing)
        <fieldset class="fieldset" id="field-age_update">
            <legend class="field__label">{{ __('Current age information: :age', ['age' => $cattle->ageLabel()]) }}</legend>
            <div class="check-grid">
                <x-ui.checkbox type="radio" name="age_update" value="keep" :label="__('Keep current age information')" :checked="$ageUpdate === 'keep'" data-age-update />
                <x-ui.checkbox type="radio" name="age_update" value="replace" :label="__('Replace age information')" :checked="$ageUpdate === 'replace'" data-age-update />
            </div>
        </fieldset>
    @endif

    <div class="stack" data-age-editor>
        <fieldset class="fieldset" id="field-age_basis" tabindex="-1">
            <legend class="field__label">{{ __('How do you know the age?') }}</legend>
            <div class="check-grid">
                <x-ui.checkbox type="radio" name="age_basis" value="exact" :label="__('Exact birth date')" :checked="$ageBasis === 'exact'" data-age-basis />
                <x-ui.checkbox type="radio" name="age_basis" value="estimated" :label="__('Estimated age')" :checked="$ageBasis === 'estimated'" data-age-basis />
                <x-ui.checkbox type="radio" name="age_basis" value="unknown" :label="__('Not known')" :checked="$ageBasis === 'unknown'" data-age-basis />
            </div>
            <p class="field__hint cattle-no-js-hint">{{ __('Fill in only the part for the option you chose. The other part is ignored.') }}</p>
        </fieldset>

        <div data-age-group="exact">
            <x-ui.field name="birth_date" type="date" :label="__('Birth date')" :value="$cattle?->birth_date?->toDateString()" :max="$today"
                :hint="__('Not in the future. Format: YYYY-MM-DD.')" />
        </div>
        <div data-age-group="estimated" class="grid-2 cattle-form__pair">
            <x-ui.field name="estimate_years" :label="__('About how many years?')" :value="$estimateYears" inputmode="numeric" :hint="__('0 to 50')" />
            <x-ui.field name="estimate_months" :label="__('And months')" :value="$estimateMonths" inputmode="numeric" optional :hint="__('0 to 11')" />
        </div>
        <p class="t-callout t-muted" data-age-group="estimated">{{ __('The estimate is saved as of today, so the age keeps counting up correctly.') }}</p>
    </div>
</section>

{{-- 3. Parents: plain text for now. Linking real parent records is F8 (family tree). --}}
<section class="cattle-form__section" aria-labelledby="section-parents">
    <h2 class="t-title-2" id="section-parents">{{ __('3. Parents') }}</h2>
    <p class="t-callout t-muted">{{ __('Write what you know. Leave empty if not known. Linking parent records comes later with the Family tree feature.') }}</p>
    <x-ui.textarea name="mother_details" :label="__('Mother (dam)').' ('.__('optional').')'" :value="$cattle?->mother_details" rows="2" maxlength="300"
        :hint="__('For example: tag T-02, my farm, Red Chittagong')" />
    <x-ui.textarea name="father_details" :label="__('Father (sire)').' ('.__('optional').')'" :value="$cattle?->father_details" rows="2" maxlength="300"
        :hint="__('For example: AI bull from the upazila livestock office')" />
</section>

@unless ($editing)
    {{-- 4. Optional first health note --}}
    <section class="cattle-form__section" aria-labelledby="section-history">
        <h2 class="t-title-2" id="section-history">{{ __('4. Health history') }} <span class="field__optional">({{ __('optional') }})</span></h2>
        <p class="t-callout t-muted">{{ __('Add one past event now if you like, such as a vaccination. Leave it empty if you do not know. You can add more entries later.') }}</p>
        @include('cattle.health-history._fields', ['prefix' => 'history_', 'entry' => null])
    </section>
@endunless
