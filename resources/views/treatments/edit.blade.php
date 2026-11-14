<x-layouts.app :title="__('Edit Treatment Record')">
    <section class="kb-section">
        <h1>{{ __('Edit Treatment Record') }}</h1>

        <form method="POST" action="{{ route('treatments.update', $treatment) }}" class="kb-form">
            @method('PATCH')
            @include('treatments._form', ['submitLabel' => __('Update Record')])
        </form>

        <p><a href="{{ route('treatments.show', $treatment) }}">&larr; {{ __('Cancel') }}</a></p>
    </section>
</x-layouts.app>