<x-layouts.app :title="__('My Visit Requests')">
    <div class="container">
        <x-ui.page-header :title="__('My Visit Requests')" :subtitle="__('Track your farm visit requests and cancel pending ones if needed.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" icon="search" :href="route('vets.index')">{{ __('Find a veterinarian') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert tone="success" role="status" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        @if($requests->isEmpty())
            <x-ui.empty-state icon="stethoscope" :title="__('You have not sent any visit requests yet.')" :description="__('Browse listed veterinarians and send a farm visit request.')">
                <x-ui.button size="md" :href="route('vets.index')">{{ __('Find a veterinarian') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="stack">
                @foreach($requests as $req)
                    @php
                        $tone = match ($req->status) {
                            'accepted' => 'success',
                            'declined', 'cancelled' => 'danger',
                            default => 'warning',
                        };
                    @endphp
                    <x-ui.card>
                        <x-slot:title>
                            <a href="{{ route('visit-requests.show', $req) }}">{{ __('Veterinarian') }}: {{ $req->veterinarianProfile->user->name }}</a>
                        </x-slot:title>
                        <x-slot:actions>
                            <x-ui.badge :tone="$tone">{{ __(ucfirst($req->status)) }}</x-ui.badge>
                        </x-slot:actions>

                        <dl class="stack stack-sm mb-4">
                            <div class="cluster cluster--between">
                                <dt class="t-muted">{{ __('Animal') }}</dt>
                                <dd>{{ __(ucfirst($req->animal_type)) }}</dd>
                            </div>
                            <div class="cluster cluster--between">
                                <dt class="t-muted">{{ __('Preferred Date') }}</dt>
                                <dd>{{ $req->preferred_date->format('d M, Y') }}</dd>
                            </div>
                            <div class="cluster cluster--between">
                                <dt class="t-muted">{{ __('Address') }}</dt>
                                <dd>{{ $req->farm_address }}</dd>
                            </div>
                            <div class="cluster cluster--between">
                                <dt class="t-muted">{{ __('Problem') }}</dt>
                                <dd>{{ $req->problem }}</dd>
                            </div>
                        </dl>

                        @if($req->vet_note)
                            <x-ui.alert tone="info" :title="__('Veterinarian Note')" class="mb-4">{{ $req->vet_note }}</x-ui.alert>
                        @endif

                        <div class="cluster">
                            <x-ui.button size="md" variant="secondary" icon="eye" :href="route('visit-requests.show', $req)">{{ __('Visit Request Details') }}</x-ui.button>
                            @if($req->status === 'pending')
                                <form method="POST" action="{{ route('visit-requests.cancel', $req) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" size="md" variant="danger" icon="x">{{ __('Cancel Request') }}</x-ui.button>
                                </form>
                            @endif
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
</x-layouts.app>