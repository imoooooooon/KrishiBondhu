<x-layouts.app :title="__('Veterinary Doctors')">
    <div class="container">
        <x-ui.page-header :title="__('Veterinary Doctors')" :subtitle="__('Find listed veterinarians by name, district, or the animals they treat.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            @auth
                @if(auth()->user()->hasRole(\App\Models\Role::FARMER))
                    <x-slot:actions>
                        <x-ui.button size="md" variant="secondary" icon="list" :href="route('visit-requests.index')">{{ __('My Visit Requests') }}</x-ui.button>
                    </x-slot:actions>
                @endif
            @endauth
        </x-ui.page-header>

        <x-ui.card class="mb-4">
            <form method="GET" action="{{ route('vets.index') }}" class="stack">
                <div class="grid-2">
                    <x-ui.field name="search" :label="__('Search')" :value="request('search')" :placeholder="__('Search by name or upazila')" />
                    <x-ui.field name="district" :label="__('District')" :value="request('district')" :placeholder="__('District')" />
                </div>
                <x-ui.select
                    name="animal"
                    :label="__('Animal Type')"
                    :value="request('animal')"
                    :options="[
                        '' => __('All Animals'),
                        'cattle' => __('Cattle'),
                        'goats' => __('Goats'),
                        'poultry' => __('Poultry'),
                    ]"
                />
                <div class="cluster">
                    <x-ui.button type="submit" size="md" icon="search">{{ __('Search') }}</x-ui.button>
                    @if(request()->hasAny(['search', 'district', 'animal']))
                        <x-ui.button size="md" variant="secondary" :href="route('vets.index')">{{ __('Reset') }}</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        @if($vets->isEmpty())
            <x-ui.empty-state icon="stethoscope" :title="__('No veterinarians found.')" :description="__('Try clearing your filters or check back later as more doctors list their profiles.')" />
        @else
            <div class="grid-2">
                @foreach($vets as $vet)
                    <x-ui.card>
                        <x-slot:title>{{ $vet->user->name }}</x-slot:title>
                        @if($vet->experience_years !== null)
                            <x-slot:actions>
                                <x-ui.badge tone="info">{{ $vet->experience_years }} {{ __('years') }}</x-ui.badge>
                            </x-slot:actions>
                        @endif

                        <dl class="stack stack-sm mb-4">
                            <div class="cluster cluster--between">
                                <dt class="t-muted">{{ __('Area') }}</dt>
                                <dd>{{ $vet->upazila }}, {{ $vet->district }}</dd>
                            </div>
                            <div class="cluster cluster--between">
                                <dt class="t-muted">{{ __('Address') }}</dt>
                                <dd>{{ $vet->address }}</dd>
                            </div>
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

                        <x-ui.button size="md" icon="arrow-right" :href="route('vets.show', $vet)">{{ __('View Profile & Request Visit') }}</x-ui.button>
                    </x-ui.card>
                @endforeach
            </div>
            <div class="mt-4">{{ $vets->links() }}</div>
        @endif
    </div>
</x-layouts.app>