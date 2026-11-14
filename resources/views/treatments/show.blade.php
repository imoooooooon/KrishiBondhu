<x-layouts.app :title="$treatment->subject_name">
    <section class="kb-section">
        <h1>{{ $treatment->subject_name }}</h1>

        @if (session('status'))
            <p role="status" class="kb-alert kb-alert-success">{{ session('status') }}</p>
        @endif

        <dl class="kb-details">
            <dt>{{ __('Category') }}</dt>
            <dd>{{ __(ucfirst($treatment->category)) }}</dd>

            <dt>{{ __('Status') }}</dt>
            <dd>{{ __(ucfirst($treatment->status)) }}</dd>

            <dt>{{ __('Treatment Date') }}</dt>
            <dd>{{ $treatment->treated_on->format('d M, Y') }}</dd>

            <dt>{{ __('Problem / Symptoms') }}</dt>
            <dd>{{ $treatment->problem }}</dd>

            <dt>{{ __('Treatment & Medicine Notes') }}</dt>
            <dd>{{ $treatment->treatment_notes }}</dd>

            @if($treatment->follow_up_date)
                <dt>{{ __('Follow-up Date') }}</dt>
                <dd>{{ $treatment->follow_up_date->format('d M, Y') }}</dd>
            @endif
        </dl>

        <p>
            <a href="{{ route('treatments.edit', $treatment) }}" class="kb-btn">{{ __('Edit Record') }}</a>
        </p>

        <form method="POST" action="{{ route('treatments.destroy', $treatment) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="kb-btn kb-btn-danger">{{ __('Delete Record') }}</button>
        </form>

        <p><a href="{{ route('treatments.index') }}">&larr; {{ __('Back to Treatment History') }}</a></p>
    </section>
</x-layouts.app>