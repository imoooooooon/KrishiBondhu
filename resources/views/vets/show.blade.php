<x-layouts.app :title="$vet->user->name">
    <div class="container">
        <x-ui.page-header :title="$vet->user->name" :subtitle="$vet->upazila . ', ' . $vet->district">
            <x-slot:breadcrumbs><a href="{{ route('vets.index') }}">{{ __('Veterinary Doctors') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="arrow-left" :href="route('vets.index')">{{ __('Back to Veterinarians') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid-2">
            <x-ui.card>
                <x-slot:title>{{ __('Doctor & Contact Details') }}</x-slot:title>
                <dl class="stack stack-sm">
                    <div class="cluster cluster--between">
                        <dt class="t-muted">{{ __('Area') }}</dt>
                        <dd>{{ $vet->upazila }}, {{ $vet->district }}</dd>
                    </div>
                    <div class="cluster cluster--between">
                        <dt class="t-muted">{{ __('Address') }}</dt>
                        <dd>{{ $vet->address }}</dd>
                    </div>
                    <div class="cluster cluster--between">
                        <dt class="t-muted">{{ __('Mobile number') }}</dt>
                        <dd class="t-num"><a href="tel:{{ $vet->user->phone }}">{{ $vet->user->phone }}</a></dd>
                    </div>
                    @if($vet->user->email)
                        <div class="cluster cluster--between">
                            <dt class="t-muted">{{ __('Email') }}</dt>
                            <dd><a href="mailto:{{ $vet->user->email }}">{{ $vet->user->email }}</a></dd>
                        </div>
                    @endif
                    @if($vet->experience_years !== null)
                        <div class="cluster cluster--between">
                            <dt class="t-muted">{{ __('Experience') }}</dt>
                            <dd>{{ $vet->experience_years }} {{ __('years') }}</dd>
                        </div>
                    @endif
                    <div class="cluster cluster--between">
                        <dt class="t-muted">{{ __('Treats') }}</dt>
                        <dd>
                            {{ collect([
                                $vet->treats_cattle ? __('Cattle') : null,
                                $vet->treats_goats ? __('Goats') : null,
                                $vet->treats_poultry ? __('Poultry') : null,
                            ])->filter()->implode(', ') ?: __('General') }}
                        </dd>
                    </div>
                </dl>
                @if($vet->bio)
                    <p class="t-callout mt-4">{{ $vet->bio }}</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <x-slot:title>{{ __('Request a Farm Visit') }}</x-slot:title>

                @auth
                    @if(auth()->user()->hasRole(\App\Models\Role::FARMER))
                        @error('veterinarian_profile_id')
                            <x-ui.alert tone="danger" role="alert" class="mb-4">{{ $message }}</x-ui.alert>
                        @enderror

                        @if($vet->is_listed)
                            @php
                                $animalOptions = collect(\App\Http\Controllers\VisitRequestController::ANIMAL_TYPES)
                                    ->mapWithKeys(fn ($type) => [$type => __(ucfirst($type))])
                                    ->all();
                            @endphp
                            <form method="POST" action="{{ route('vets.visit-requests.store', $vet) }}" class="stack">
                                @csrf
                                <x-ui.select
                                    name="animal_type"
                                    :label="__('Animal Type')"
                                    :options="$animalOptions"
                                    :placeholder="__('Select animal type')"
                                    required
                                />
                                <x-ui.field
                                    name="preferred_date"
                                    type="date"
                                    :label="__('Preferred Date')"
                                    :min="date('Y-m-d')"
                                    required
                                />
                                <x-ui.field
                                    name="farm_address"
                                    :label="__('Farm Address')"
                                    maxlength="255"
                                    required
                                />
                                <x-ui.textarea
                                    name="problem"
                                    :label="__('Problem Description')"
                                    rows="3"
                                    required
                                />
                                <div>
                                    <x-ui.button type="submit" icon="stethoscope">{{ __('Send Request') }}</x-ui.button>
                                </div>
                            </form>
                        @else
                            <x-ui.alert tone="warning">{{ __('This veterinarian is not currently accepting visit requests.') }}</x-ui.alert>
                        @endif
                    @else
                        <x-ui.alert tone="info">{{ __('Only accounts with the Farmer role can send farm visit requests.') }}</x-ui.alert>
                    @endif
                @else
                    <p class="t-callout t-muted mb-4">{{ __('Sign in with a Farmer account to request a farm visit.') }}</p>
                    <x-ui.button size="md" icon="log-in" :href="route('login')">{{ __('Sign in') }}</x-ui.button>
                @endauth
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>