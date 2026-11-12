{{-- Shown by App\Http\Controllers\PlannedController for routes that are mapped in docs/IA.md but not built yet. --}}
<x-layouts.app :title="$title" narrow>
    <x-ui.page-header :title="$title" eyebrow="{{ __('Planned page') }}"
        subtitle="{{ __('This route is part of the Krishi Bondhu information architecture. Its page has not been built yet.') }}" />

    <div class="stack">
        <x-ui.card>
            <dl class="stack stack-sm">
                <div class="cluster cluster--between"><dt class="t-muted">{{ __('Route name') }}</dt><dd><code>{{ $routeName }}</code></dd></div>
                <div class="cluster cluster--between"><dt class="t-muted">{{ __('Request') }}</dt><dd><code>{{ $method }} {{ $uri }}</code></dd></div>
                <div class="cluster cluster--between"><dt class="t-muted">{{ __('Status') }}</dt><dd><x-ui.badge tone="info" icon="clock">{{ __('Planned') }}</x-ui.badge></dd></div>
            </dl>
        </x-ui.card>

        <x-ui.alert tone="neutral" title="{{ __('For the team') }}">
            {{ __('Find this route in docs/IA.md to see its feature, owner and access rules. To build it, create your controller and replace PlannedController for this route in routes/modules/, keeping the same URI and route name.') }}
        </x-ui.alert>

        <div class="cluster">
            <x-ui.button :href="route('home')" variant="secondary" icon="arrow-left">{{ __('Home') }}</x-ui.button>
            <x-ui.button href="https://github.com/imoooooooon/KrishiBondhu/blob/main/docs/IA.md" variant="tertiary" icon-after="external-link">{{ __('Open IA.md') }}</x-ui.button>
        </div>
    </div>
</x-layouts.app>
