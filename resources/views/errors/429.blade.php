{{-- 429 Too Many Requests: a rate limit was reached (e.g. publishing many crop listings within one minute). --}}
<x-layouts.app :title="__('Please wait a moment')" narrow>
    <x-ui.page-header :title="__('Please wait a moment')" />
    <div class="stack">
        <x-ui.alert tone="warning">
            {{ __('You sent too many requests in a short time. Wait one minute, then try again. Nothing new was saved.') }}
        </x-ui.alert>
        <div class="cluster">
            <x-ui.button icon="house" :href="route('home')">{{ __('Home') }}</x-ui.button>
        </div>
    </div>
</x-layouts.app>
