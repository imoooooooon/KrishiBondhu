<x-layouts.app :title="__('Add Treatment Record')">
    <section class="kb-section">
        <h1>{{ __('Add Treatment Record') }}</h1>

        <form method="POST" action="{{ route('treatments.store') }}" class="kb-form">
            @include('treatments._form', ['submitLabel' => __('Save Record')])
        </form>

        <p><a href="{{ route('treatments.index') }}">&larr; {{ __('Back to Treatment History') }}</a></p>
    </section>
</x-layouts.app>