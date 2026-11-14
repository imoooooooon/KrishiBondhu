<x-layouts.app :title="__('Treatment History')">
    <div class="container">
        <x-ui.page-header :title="__('Treatment History')" :subtitle="__('Keep private treatment records for your crops and livestock.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" icon="plus" :href="route('treatments.create')">{{ __('Add Treatment Record') }}</x-ui.button>
                <x-ui.button size="md" variant="secondary" icon="bell" :href="route('reminders.index')">{{ __('Follow-up Reminders') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert tone="success" role="status" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="mb-4">
            <form method="GET" action="{{ route('treatments.index') }}" class="stack">
                <div class="grid-2">
                    <x-ui.select
                        name="category"
                        :label="__('Category')"
                        :value="request('category')"
                        :options="[
                            '' => __('All Categories'),
                            'crop' => __('Crop'),
                            'animal' => __('Animal'),
                        ]"
                    />
                    <x-ui.select
                        name="status"
                        :label="__('Status')"
                        :value="request('status')"
                        :options="[
                            '' => __('All Statuses'),
                            'ongoing' => __('Ongoing'),
                            'completed' => __('Completed'),
                        ]"
                    />
                </div>
                <div class="cluster">
                    <x-ui.button type="submit" size="md" icon="search">{{ __('Filter') }}</x-ui.button>
                    @if(request()->hasAny(['category', 'status']))
                        <x-ui.button size="md" variant="secondary" :href="route('treatments.index')">{{ __('Reset') }}</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        @if($treatments->isEmpty())
            <x-ui.empty-state icon="heart-pulse" :title="__('No treatment records yet.')" :description="__('Add your first crop or animal treatment record to keep notes and schedule follow-up reminders.')">
                <x-ui.button size="md" icon="plus" :href="route('treatments.create')">{{ __('Add Treatment Record') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="grid-2">
                @foreach($treatments as $treatment)
                    <x-ui.card>
                        <x-slot:title>
                            <a href="{{ route('treatments.show', $treatment) }}">{{ $treatment->subject_name }}</a>
                        </x-slot:title>
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
                        </dl>

                        <x-ui.button size="md" variant="secondary" icon="eye" :href="route('treatments.show', $treatment)">{{ __('View Details') }}</x-ui.button>
                    </x-ui.card>
                @endforeach
            </div>
            <div class="mt-4">{{ $treatments->links() }}</div>
        @endif
    </div>
</x-layouts.app>