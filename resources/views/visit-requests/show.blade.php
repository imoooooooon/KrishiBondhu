<x-layouts.app :title="__('Visit Request Details')">
    <div class="container">
        <x-ui.page-header :title="__('Visit Request Details')" :subtitle="$visitRequest->veterinarianProfile->user->name">
            <x-slot:breadcrumbs><a href="{{ route('visit-requests.index') }}">{{ __('My Visit Requests') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="arrow-left" :href="route('visit-requests.index')">{{ __('Back to List') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @php
            $tone = match ($visitRequest->status) {
                'accepted' => 'success',
                'declined', 'cancelled' => 'danger',
                default => 'warning',
            };
        @endphp

        <x-ui.card>
            <x-slot:title>{{ __('Veterinarian') }}: {{ $visitRequest->veterinarianProfile->user->name }}</x-slot:title>
            <x-slot:actions>
                <x-ui.badge :tone="$tone">{{ __(ucfirst($visitRequest->status)) }}</x-ui.badge>
            </x-slot:actions>

            <dl class="stack stack-sm">
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Mobile number') }}</dt>
                    <dd class="t-num">{{ $visitRequest->veterinarianProfile->user->phone }}</dd>
                </div>
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Animal') }}</dt>
                    <dd>{{ __(ucfirst($visitRequest->animal_type)) }}</dd>
                </div>
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Preferred Date') }}</dt>
                    <dd>{{ $visitRequest->preferred_date->format('d M, Y') }}</dd>
                </div>
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Address') }}</dt>
                    <dd>{{ $visitRequest->farm_address }}</dd>
                </div>
                <div class="cluster cluster--between">
                    <dt class="t-muted">{{ __('Problem') }}</dt>
                    <dd>{{ $visitRequest->problem }}</dd>
                </div>
                @if($visitRequest->vet_note)
                    <div class="cluster cluster--between">
                        <dt class="t-muted">{{ __('Veterinarian Note') }}</dt>
                        <dd>{{ $visitRequest->vet_note }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>
    </div>
</x-layouts.app>