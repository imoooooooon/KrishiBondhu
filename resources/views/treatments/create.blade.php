<x-layouts.app :title="__('Add Treatment Record')">
    <div class="container">
        <x-ui.page-header :title="__('Add Treatment Record')" :subtitle="__('Save crop or animal health problems, medicine notes, and an optional follow-up date.')">
            <x-slot:breadcrumbs><a href="{{ route('treatments.index') }}">{{ __('Treatment History') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="arrow-left" :href="route('treatments.index')">{{ __('Back to Treatment History') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form method="POST" action="{{ route('treatments.store') }}" class="stack">
                @include('treatments._form', ['submitLabel' => __('Save Record')])
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>