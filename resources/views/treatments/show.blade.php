<x-layouts.app :title="$treatment->subject_name">
    <div class="container">
        <x-ui.page-header :title="$treatment->subject_name" :subtitle="__(ucfirst($treatment->category))">
            <x-slot:breadcrumbs><a href="{{ route('treatments.index') }}">{{ __('Treatment History') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="pencil" :href="route('treatments.edit', $treatment)">{{ __('Edit Record') }}</x-ui.button>
                <x-ui.button size="md" variant="secondary" icon="arrow-left" :href="route('treatments.index')">{{ __('Back to Treatment History') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert tone="success" role="status" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <x-slot:title>{{ __('Treatment Details') }}</x-slot:title>
            <x-slot:actions>
                <x-ui.badge :tone="$treatment->status === 'completed' ? 'success' : 'warning'">
                    {{ __(ucfirst($treatment->status)) }}
                </x-ui.badge>
            </x-slot:actions>

            <dl class="stack stack-sm mb-4">
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Category') }}</dt>
                    <dd>{{ __(ucfirst($treatment->category)) }}</dd>
                </div>
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Treatment Date') }}</dt>
                    <dd>{{ $treatment->treated_on->format('d M, Y') }}</dd>
                </div>
                @if($treatment->follow_up_date)
                    <div class="cluster cluster--between">
                        <dt class="t-muted">{{ __('Follow-up Date') }}</dt>
                        <dd>{{ $treatment->follow_up_date->format('d M, Y') }}</dd>
                    </div>
                @endif
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Problem / Symptoms') }}</dt>
                    <dd>{{ $treatment->problem }}</dd>
                </div>
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Treatment & Medicine Notes') }}</dt>
                    <dd>{{ $treatment->treatment_notes }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('treatments.destroy', $treatment) }}">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" size="md" variant="danger" icon="trash-2">{{ __('Delete Record') }}</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>