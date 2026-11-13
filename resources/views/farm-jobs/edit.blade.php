{{-- Edit job (route: farmer.jobs.edit, owner only). F20. Controller: FarmJobController@edit / @update. --}}
<x-layouts.app :title="__('Edit job')" narrow>
    <x-ui.page-header :title="__('Edit job')" :subtitle="__('Change the details of this job. To stop recruiting, close the job from its page.')">
        <x-slot:breadcrumbs><a href="{{ route('farmer.jobs.index') }}">{{ __('My posted jobs') }}</a></x-slot:breadcrumbs>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('farmer.jobs.update', $job) }}" class="stack" novalidate>
            @csrf
            @method('PUT')

            @include('farm-jobs._form', ['job' => $job])

            <div class="cluster">
                <x-ui.button type="submit" icon="check">{{ __('Save changes') }}</x-ui.button>
                <x-ui.button variant="tertiary" :href="route('farmer.jobs.show', $job)">{{ __('Cancel') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-slot:scripts><script src="{{ asset('js/farm-jobs.js') }}" defer></script></x-slot:scripts>
</x-layouts.app>
