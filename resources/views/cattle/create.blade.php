{{-- "Register cattle" (route cattle.create, Farmer role). F6. Controller: CattleController@create. --}}
<x-layouts.app :title="__('Register cattle')" narrow>
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/cattle.css').'?v='.@filemtime(public_path('css/cattle.css')) }}"></x-slot:head>

    <x-ui.page-header :title="__('Register cattle')" :subtitle="__('Add one animal to your private herd. Only you can see its profile.')">
        <x-slot:breadcrumbs>
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <x-ui.icon name="chevron-right" size="14" />
            <a href="{{ route('cattle.index') }}">{{ __('My cattle') }}</a>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <form method="POST" action="{{ route('cattle.store') }}" enctype="multipart/form-data" class="stack stack-lg" id="cattle-form" data-cattle-form>
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">
        @include('cattle._form')
        <div class="cluster">
            <x-ui.button type="submit" icon="plus" data-submit-once>{{ __('Register cattle') }}</x-ui.button>
            <x-ui.button variant="tertiary" :href="route('cattle.index')">{{ __('Cancel') }}</x-ui.button>
        </div>
    </form>

    <x-slot:scripts><script src="{{ asset('js/cattle.js').'?v='.@filemtime(public_path('js/cattle.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
