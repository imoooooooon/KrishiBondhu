<x-layouts.app :title="__('Follow-up Reminders')">
    <section class="kb-section">
        <h1>{{ __('Follow-up Reminders') }}</h1>

        @if (session('status'))
            <p role="status" class="kb-alert kb-alert-success">{{ session('status') }}</p>
        @endif

        @if($reminders->isEmpty())
            <p>{{ __('No reminders scheduled.') }}</p>
        @else
            <div class="kb-grid">
                @foreach($reminders as $reminder)
                    <article class="kb-card">
                        <h2>{{ $reminder->title }}</h2>
                        <p><strong>{{ __('Date') }}:</strong> {{ $reminder->remind_on->format('d M, Y') }}</p>
                        @if($reminder->notes)
                            <p>{{ $reminder->notes }}</p>
                        @endif
                        @if($reminder->isDone())
                            <p><strong>{{ __('Completed') }}:</strong> {{ $reminder->completed_at->format('d M, Y') }}</p>
                        @else
                            <form method="POST" action="{{ route('reminders.complete', $reminder) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="kb-btn kb-btn-primary">{{ __('Mark as Done') }}</button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>
            {{ $reminders->links() }}
        @endif

        <p><a href="{{ route('treatments.index') }}">&larr; {{ __('Back to Treatment History') }}</a></p>
    </section>
</x-layouts.app>