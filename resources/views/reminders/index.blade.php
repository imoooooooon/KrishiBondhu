<x-layouts.app :title="__('Follow-up Reminders')">
    <div class="container">
        <x-ui.page-header :title="__('Follow-up Reminders')" :subtitle="__('Upcoming and completed follow-up dates for your treatments.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="arrow-left" :href="route('treatments.index')">{{ __('Back to Treatment History') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert tone="success" role="status" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        @if($reminders->isEmpty())
            <x-ui.empty-state icon="bell" :title="__('No reminders scheduled.')" :description="__('Add a follow-up date on an ongoing treatment record to create a reminder automatically.')">
                <x-ui.button size="md" :href="route('treatments.index')">{{ __('Back to Treatment History') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="grid-2">
                @foreach($reminders as $reminder)
                    <x-ui.card>
                        <x-slot:title>{{ $reminder->title }}</x-slot:title>
                        <x-slot:actions>
                            <x-ui.badge :tone="$reminder->isDone() ? 'success' : 'warning'">
                                {{ $reminder->isDone() ? __('Completed') : __('Ongoing') }}
                            </x-ui.badge>
                        </x-slot:actions>

                        <dl class="stack stack-sm mb-4">
                            <div class="cluster cluster--between">
                                <dt class="t-muted">{{ __('Follow-up Date') }}</dt>
                                <dd>{{ $reminder->remind_on->format('d M, Y') }}</dd>
                            </div>
                            @if($reminder->notes)
                                <div class="cluster cluster--between">
                                    <dt class="t-muted">{{ __('Notes') }}</dt>
                                    <dd>{{ $reminder->notes }}</dd>
                                </div>
                            @endif
                            @if($reminder->isDone())
                                <div class="cluster cluster--between">
                                    <dt class="t-muted">{{ __('Completed') }}</dt>
                                    <dd>{{ $reminder->completed_at->format('d M, Y') }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if(! $reminder->isDone())
                            <form method="POST" action="{{ route('reminders.complete', $reminder) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.button type="submit" size="md" icon="check">{{ __('Mark as Done') }}</x-ui.button>
                            </form>
                        @endif
                    </x-ui.card>
                @endforeach
            </div>
            <div class="mt-4">{{ $reminders->links() }}</div>
        @endif
    </div>
</x-layouts.app>