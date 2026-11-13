{{-- "Edit profile" (route cattle.edit, owner only). Sends operation=save and the version this page was opened with. --}}
<x-layouts.app :title="__('Edit profile')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/cattle.css').'?v='.@filemtime(public_path('css/cattle.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Edit profile')" :subtitle="$cattle->title()" class="cattle-text">
        <x-slot:breadcrumbs>
            <a href="{{ route('cattle.index') }}">{{ __('My cattle') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('cattle.show', $cattle) }}" class="cattle-text">{{ $cattle->title() }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>
    <p class="t-callout t-muted mb-6">{{ __('KB ID') }}: <span class="cattle-id">{{ $cattle->displayId() }}</span> · {{ __('The KB ID never changes.') }}</p>

    <form method="POST" action="{{ route('cattle.update', $cattle) }}" enctype="multipart/form-data" class="stack stack-lg" id="cattle-form" data-cattle-form>
        @csrf
        @method('PATCH')
        <input type="hidden" name="operation" value="save">
        <input type="hidden" name="version" value="{{ old('version', $cattle->version) }}">
        @include('cattle._form', ['cattle' => $cattle])
        <div class="cluster">
            <x-ui.button type="submit" icon="check" data-submit-once>{{ __('Save changes') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('cattle.show', $cattle)">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>

    <x-slot:scripts><script src="{{ asset('js/cattle.js').'?v='.@filemtime(public_path('js/cattle.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
