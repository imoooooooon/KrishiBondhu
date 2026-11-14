<x-layouts.app :title="__('Incoming Visit Requests')">
    <div class="container">
        <x-ui.page-header :title="__('Incoming Visit Requests')" :subtitle="__('Review farm visit requests sent by farmers and accept or decline them.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="user" :href="route('vet.profile.edit')">{{ __('Veterinarian Profile') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert tone="success" role="status" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        @if($requests->isEmpty())
            <x-ui.empty-state icon="stethoscope" :title="__('No visit requests received yet.')" :description="__('When farmers request a farm visit from your public profile, they will appear here.')" />
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
                        <x-slot:title>{{ __('Farmer') }}: {{ $req->farmer->name }}</x-slot:title>
                        <x-slot:actions>
                            <x-ui.badge :tone="$tone">{{ strtoupper($req->status) }}</x-ui.badge>
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

                        @if($req->status === 'pending')
                            <form method="POST" action="{{ route('vet.visit-requests.update', $req) }}" class="stack">
                                @csrf
                                @method('PATCH')
                                <x-ui.field name="vet_note" :label="__('Note for farmer')" :placeholder="__('Optional note for farmer')" optional />
                                <div class="cluster">
                                    <x-ui.button type="submit" size="md" name="status" value="accepted" icon="check">{{ __('Accept') }}</x-ui.button>
                                    <x-ui.button type="submit" size="md" variant="danger" name="status" value="declined" icon="x">{{ __('Decline') }}</x-ui.button>
                                </div>
                            </form>
                        @elseif($req->vet_note)
                            <x-ui.alert tone="info" :title="__('Your Note')">{{ $req->vet_note }}</x-ui.alert>
                        @endif
                    </x-ui.card>
                @endforeach
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
</x-layouts.app>