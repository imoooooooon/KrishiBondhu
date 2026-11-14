<x-layouts.app :title="__('Edit Treatment Record')">
    <div class="container">
        <x-ui.page-header :title="__('Edit Treatment Record')" :subtitle="$treatment->subject_name">
            <x-slot:breadcrumbs><a href="{{ route('treatments.index') }}">{{ __('Treatment History') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="arrow-left" :href="route('treatments.show', $treatment)">{{ __('Cancel') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form method="POST" action="{{ route('treatments.update', $treatment) }}" class="stack">
                @method('PATCH')
                @include('treatments._form', ['submitLabel' => __('Update Record')])
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>