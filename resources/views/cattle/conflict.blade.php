{{--
    409 Conflict (rendered by App\Exceptions\CattleConflictException). Nothing was saved because the profile or the
    health entry changed after this form was opened. Shows what the farmer typed so they can enter it again.
--}}
<x-layouts.app :title="__('This record changed')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/cattle.css') }}"></x-slot:head>
    <x-ui.page-header :title="__('This record changed after you opened it')">
        <x-slot:breadcrumbs><a href="{{ route('cattle.index') }}">{{ __('My cattle') }}</a></x-slot:breadcrumbs>
    </x-ui.page-header>

    <div class="stack">
        <x-ui.alert tone="warning" role="alert" :title="__('Your changes were not saved.')">
            {{ __('It was changed in another tab or on another phone. Open the latest version and enter your changes again.') }}
        </x-ui.alert>

        @if ($submitted !== [])
            <x-ui.card tone="surface" class="stack">
                <h2 class="t-title-3">{{ __('What you sent') }}</h2>
                <dl class="cattle-facts">
                    @foreach ($submitted as $field => $value)
                        <div><dt>{{ __(str_replace('_', ' ', $field)) }}</dt><dd class="cattle-text cattle-text--lines">{{ $value }}</dd></div>
                    @endforeach
                </dl>
                <p class="t-caption t-muted">{{ __('A new photo you chose was not kept. Choose it again in the latest form.') }}</p>
            </x-ui.card>
        @endif

        <div class="cluster">
            @if ($entry)
                <x-ui.button icon="pencil" :href="route('cattle.health-history.edit', [$cattle, $entry])" target="_blank" rel="noopener">{{ __('Open the latest entry (new tab)') }}</x-ui.button>
            @elseif (! $cattle->isArchived())
                <x-ui.button icon="pencil" :href="route('cattle.edit', $cattle)" target="_blank" rel="noopener">{{ __('Open the latest edit form (new tab)') }}</x-ui.button>
            @endif
            <x-ui.button variant="secondary" :href="route('cattle.show', $cattle)">{{ __('Back to the profile') }}</x-ui.button>
        </div>
    </div>
</x-layouts.app>
