{{-- "Add health entry" (route cattle.health-history.create, owner only, active cattle). --}}
<x-layouts.app :title="__('Add health entry')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/cattle.css').'?v='.@filemtime(public_path('css/cattle.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Add health entry')" :subtitle="__('Record something that happened in the past, such as a vaccination or a vet visit.')">
        <x-slot:breadcrumbs>
            <a href="{{ route('cattle.index') }}">{{ __('My cattle') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('cattle.show', $cattle) }}" class="cattle-text">{{ $cattle->title() }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>
    @include('cattle.health-history._summary')

    <form method="POST" action="{{ route('cattle.health-history.store', $cattle) }}" class="stack" id="cattle-form" data-cattle-form>
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">
        @include('cattle.health-history._errors')
        @include('cattle.health-history._fields', ['prefix' => '', 'entry' => null])
        <div class="cluster">
            <x-ui.button type="submit" icon="check" data-submit-once>{{ __('Save entry') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('cattle.show', $cattle)">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>

    <x-slot:scripts><script src="{{ asset('js/cattle.js').'?v='.@filemtime(public_path('js/cattle.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
