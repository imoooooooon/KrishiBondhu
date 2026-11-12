{{-- Join (route: register). Supporting page, not a counted feature. Controller: Auth\RegisteredUserController. --}}
@php
    $roleHelp = [
        'farmer' => __('Sell crops, keep cattle records, hire workers'),
        'buyer' => __('Buy crops and farm supplies'),
        'worker' => __('Find farm work and get booked'),
        'insemination-agent' => __('Record cattle insemination'),
        'price-contributor' => __('Enter local market prices'),
        'veterinarian' => __('Help farmers with animal health'),
        'seller' => __('Sell fertilizer, seeds and tools'),
    ];
    $roleError = $errors->first('roles') ?: $errors->first('roles.*');
@endphp
<x-layouts.app :title="__('Create your account')" narrow>
    <x-ui.page-header :title="__('Create your account')" :subtitle="__('One account for everything you do on Krishi Bondhu. It is free.')" />

    <x-ui.card class="stack">
        <form method="POST" action="{{ route('register.store') }}" class="stack" novalidate>
            @csrf

            <x-ui.field name="name" :label="__('Full name')" autocomplete="name" required autofocus />

            <x-ui.field name="phone" type="tel" :label="__('Mobile number')" :hint="__('You will log in with this number. 11 digits, like 01712345678')"
                inputmode="tel" autocomplete="tel" required />

            <x-ui.field name="email" type="email" :label="__('Email')" optional autocomplete="email" />

            <fieldset class="fieldset" @if ($roleError) aria-describedby="roles-error" @endif>
                <legend class="field__label">{{ __('How will you use Krishi Bondhu?') }}</legend>
                <p class="field__hint">{{ __('Choose all that apply. You can add more later.') }}</p>
                <div class="check-grid">
                    @foreach ($roles as $role)
                        <x-ui.checkbox name="roles[]" :value="$role->slug"
                            :label="__($role->name).' · '.($roleHelp[$role->slug] ?? '')" />
                    @endforeach
                </div>
                @if ($roleError)<p class="field__error" id="roles-error"><x-ui.icon name="circle-alert" size="16" />{{ $roleError }}</p>@endif
            </fieldset>

            <x-ui.field name="password" type="password" :label="__('Password')" :hint="__('At least 8 characters')"
                autocomplete="new-password" required />

            <x-ui.field name="password_confirmation" type="password" :label="__('Confirm password')"
                autocomplete="new-password" required />

            <x-ui.button type="submit" icon="user" block>{{ __('Create account') }}</x-ui.button>
        </form>
    </x-ui.card>

    <p class="t-callout t-muted text-center mt-6">
        {{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Log in') }}</a>
    </p>
</x-layouts.app>
