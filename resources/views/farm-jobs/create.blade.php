{{-- Post a job (route: farmer.jobs.create, farmer only). F20. Controller: FarmJobController@create / @store. --}}
<x-layouts.app :title="__('Post a job')" narrow>
    <x-ui.page-header :title="__('Post a job')" :subtitle="__('Tell workers what the work is, where and when, and what you offer.')">
        <x-slot:breadcrumbs><a href="{{ route('farmer.jobs.index') }}">{{ __('My posted jobs') }}</a></x-slot:breadcrumbs>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('farmer.jobs.store') }}" class="stack" novalidate>
            @csrf

            @include('farm-jobs._form')

            <div class="cluster">
                <x-ui.button type="submit" icon="check">{{ __('Publish job') }}</x-ui.button>
                <x-ui.button variant="tertiary" :href="route('farmer.jobs.index')">{{ __('Cancel') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-slot:scripts><script src="{{ asset('js/farm-jobs.js') }}" defer></script></x-slot:scripts>
</x-layouts.app>
