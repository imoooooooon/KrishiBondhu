<x-layouts.app :title="__('Treatment History')">
    <section class="kb-section">
        <h1>{{ __('Treatment History') }}</h1>

        <p>
            <a href="{{ route('treatments.create') }}" class="kb-btn kb-btn-primary">{{ __('Add Treatment Record') }}</a>
            <a href="{{ route('reminders.index') }}" class="kb-btn">{{ __('Follow-up Reminders') }}</a>
        </p>

        @if (session('status'))
            <p role="status" class="kb-alert kb-alert-success">{{ session('status') }}</p>
        @endif

        <form method="GET" action="{{ route('treatments.index') }}" class="kb-filter-bar">
            <select name="category">
                <option value="">{{ __('All Categories') }}</option>
                <option value="crop" @selected(request('category') === 'crop')>{{ __('Crop') }}</option>
                <option value="animal" @selected(request('category') === 'animal')>{{ __('Animal') }}</option>
            </select>
            <select name="status">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="ongoing" @selected(request('status') === 'ongoing')>{{ __('Ongoing') }}</option>
                <option value="completed" @selected(request('status') === 'completed')>{{ __('Completed') }}</option>
            </select>
            <button type="submit" class="kb-btn">{{ __('Filter') }}</button>
        </form>

        @if($treatments->isEmpty())
            <p>{{ __('No treatment records yet.') }}</p>
        @else
            <div class="kb-grid">
                @foreach($treatments as $treatment)
                    <article class="kb-card">
                        <h2><a href="{{ route('treatments.show', $treatment) }}">{{ $treatment->subject_name }}</a></h2>
                        <p><strong>{{ __('Category') }}:</strong> {{ __(ucfirst($treatment->category)) }} | <strong>{{ __('Status') }}:</strong> {{ __(ucfirst($treatment->status)) }}</p>
                        <p><strong>{{ __('Treatment Date') }}:</strong> {{ $treatment->treated_on->format('d M, Y') }}</p>
                        @if($treatment->follow_up_date)
                            <p><strong>{{ __('Follow-up Date') }}:</strong> {{ $treatment->follow_up_date->format('d M, Y') }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
            {{ $treatments->links() }}
        @endif
    </section>
</x-layouts.app>