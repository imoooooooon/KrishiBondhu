{{-- "Edit health entry" (route cattle.health-history.edit). A correction of the farmer's own note; no delete in F6. --}}
<x-layouts.app :title="__('Edit health entry')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/cattle.css').'?v='.@filemtime(public_path('css/cattle.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Edit health entry')" :subtitle="__('Correct this note. The change is marked as edited.')">
        <x-slot:breadcrumbs>
            <a href="{{ route('cattle.index') }}">{{ __('My cattle') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('cattle.show', $cattle) }}" class="cattle-text">{{ $cattle->title() }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>
    @include('cattle.health-history._summary')

    <form method="POST" action="{{ route('cattle.health-history.update', [$cattle, $entry]) }}" class="stack" id="cattle-form" data-cattle-form>
        @csrf
        @method('PATCH')
        <input type="hidden" name="version" value="{{ old('version', $entry->version) }}">
        @include('cattle.health-history._errors')
        @include('cattle.health-history._fields', ['prefix' => '', 'entry' => $entry])
        <div class="cluster">
            <x-ui.button type="submit" icon="check" data-submit-once>{{ __('Save entry') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('cattle.show', $cattle)">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>

    <x-slot:scripts><script src="{{ asset('js/cattle.js').'?v='.@filemtime(public_path('js/cattle.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
