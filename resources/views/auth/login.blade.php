{{-- Log in (route: login). Supporting page, not a counted feature. Controller: Auth\AuthenticatedSessionController. --}}
<x-layouts.app :title="__('Log in')" narrow>
    <x-ui.page-header :title="__('Log in')" :subtitle="__('Use the mobile number you joined with.')" />

    <x-ui.card class="stack">
        <form method="POST" action="{{ route('login.store') }}" class="stack" novalidate>
            @csrf

            <x-ui.field name="phone" type="tel" :label="__('Mobile number')" :hint="__('11 digits, like 01712345678')"
                inputmode="tel" autocomplete="tel" required autofocus />

            <x-ui.field name="password" type="password" :label="__('Password')" autocomplete="current-password" required />

            <x-ui.checkbox name="remember" :label="__('Keep me logged in on this device')" />

            <x-ui.button type="submit" icon="log-in" block>{{ __('Log in') }}</x-ui.button>
        </form>
    </x-ui.card>

    <p class="t-callout t-muted text-center mt-6">
        {{ __('New to Krishi Bondhu?') }} <a href="{{ route('register') }}">{{ __('Create a free account') }}</a>
    </p>
</x-layouts.app>
