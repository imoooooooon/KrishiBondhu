<x-layouts.app :title="__('Visit Request Details')">
    <section class="kb-section">
        <h1>{{ __('Visit Request Details') }}</h1>
        <dl class="kb-details">
            <dt>{{ __('Veterinarian') }}</dt>
            <dd>{{ $visitRequest->veterinarianProfile->user->name }}</dd>

            <dt>{{ __('Status') }}</dt>
            <dd>{{ strtoupper($visitRequest->status) }}</dd>

            <dt>{{ __('Animal') }}</dt>
            <dd>{{ __(ucfirst($visitRequest->animal_type)) }}</dd>

            <dt>{{ __('Preferred Date') }}</dt>
            <dd>{{ $visitRequest->preferred_date->format('d M, Y') }}</dd>

            <dt>{{ __('Address') }}</dt>
            <dd>{{ $visitRequest->farm_address }}</dd>

            <dt>{{ __('Problem') }}</dt>
            <dd>{{ $visitRequest->problem }}</dd>

            @if($visitRequest->vet_note)
                <dt>{{ __('Veterinarian Note') }}</dt>
                <dd>{{ $visitRequest->vet_note }}</dd>
            @endif
        </dl>
        <a href="{{ route('visit-requests.index') }}">&larr; {{ __('Back to List') }}</a>
    </section>
</x-layouts.app>