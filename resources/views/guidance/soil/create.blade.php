{{--
    "Get soil guidance" form (route guidance.soil.create, Farmer role). F15. Controller: SoilGuidanceController@create.
    The farmer describes what they see: no laboratory values are asked. Works without JavaScript;
    public/js/soil-guidance.js only previews the photo and shows "please wait" after the button is pressed.
--}}
<x-layouts.app :title="__('Get soil guidance')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/soil-guidance.css').'?v='.@filemtime(public_path('css/soil-guidance.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Get soil guidance')"
        :subtitle="__('Tell us about your crop and what your soil looks like. You will get general soil-care and fertilizer guidance. You do not need a laboratory test.')">
        <x-slot:breadcrumbs>
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('guidance.soil.index') }}">{{ __('My soil guidance') }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    @if ($errors->any())
        <x-ui.alert tone="danger" role="alert" tabindex="-1" class="mb-6" data-error-summary :title="__('Please check the marked fields.')">
            {{ $errors->first('submission_token') ?: __('Your answers are still here. If you chose a photo, please choose it again.') }}
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('guidance.soil.store') }}" enctype="multipart/form-data" class="stack stack-lg" data-soil-form>
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">

        <section class="soil-form__section" aria-labelledby="section-crop">
            <h2 class="t-title-2" id="section-crop">{{ __('1. Your crop') }}</h2>
            <x-ui.field name="crop_name" :label="__('Crop name')" maxlength="100" required
                :hint="__('The crop you grow or plan to grow on this soil. For example: Boro rice, potato, jute.')" />
        </section>

        <section class="soil-form__section" aria-labelledby="section-soil">
            <h2 class="t-title-2" id="section-soil">{{ __('2. Your soil') }}</h2>
            <p class="t-callout t-muted">{{ __('Describe the soil as you see it. Choose “Not sure” if you cannot tell.') }}</p>

            <x-ui.select name="soil_color" :label="__('Soil colour')" :options="\App\Models\SoilGuidanceRecord::soilColorLabels()"
                :placeholder="__('Choose one')" required :hint="__('Look at a handful of soil in daylight.')" />
            <x-ui.select name="moisture" :label="__('Moisture')" :options="\App\Models\SoilGuidanceRecord::moistureLabels()"
                :placeholder="__('Choose one')" required :hint="__('How wet is the soil today?')" />
            <x-ui.select name="texture" :label="__('Texture')" :options="\App\Models\SoilGuidanceRecord::textureLabels()"
                :placeholder="__('Choose one')" required :hint="__('Rub a little wet soil between your fingers. How does it feel?')" />

            <x-ui.textarea name="observations" :label="__('Other things you noticed').' ('.__('optional').')'" rows="4" maxlength="2000"
                :hint="__('For example: water stands after rain, a white crust on top, very hard when dry, many earthworms.')" />
        </section>

        <section class="soil-form__section" aria-labelledby="section-photo">
            <h2 class="t-title-2" id="section-photo">{{ __('3. Soil photo') }} <span class="field__optional">({{ __('optional') }})</span></h2>
            <div class="field">
                <label class="field__label" for="field-photo">{{ __('Photo of the soil') }}</label>
                <input id="field-photo" name="photo" type="file" class="input soil-file" accept="image/jpeg,image/png"
                    aria-describedby="field-photo-hint @error('photo') field-photo-error @enderror" @error('photo') aria-invalid="true" @enderror data-photo-input>
                <p class="field__hint" id="field-photo-hint">{{ __('A photo is optional. You can continue without one. One JPG or PNG photo, up to 5 MB. Only you can see it.') }}</p>
                @error('photo')<p class="field__error" id="field-photo-error"><x-ui.icon name="circle-alert" size="16" />{{ $message }}</p>@enderror
                <img class="soil-photo-preview" alt="{{ __('Preview of the photo you chose') }}" data-photo-preview hidden>
            </div>
        </section>

        <x-ui.alert tone="info">
            {{ __('Your observations and the optional photo will be sent to an AI service (Google Gemini) to write the guidance. Your name and phone number are not sent.') }}
        </x-ui.alert>

        <div class="cluster">
            <x-ui.button type="submit" icon="sprout" data-submit-once>{{ __('Get guidance') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('guidance.soil.index')">{{ __('Cancel') }}</x-ui.button>
        </div>
        <p class="t-callout soil-wait" role="status" data-processing-note hidden>
            <x-ui.icon name="clock" size="16" />
            {{ __('Preparing your guidance. This can take up to half a minute. Please keep this page open.') }}
        </p>
    </form>

    <x-slot:scripts><script src="{{ asset('js/soil-guidance.js').'?v='.@filemtime(public_path('js/soil-guidance.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
