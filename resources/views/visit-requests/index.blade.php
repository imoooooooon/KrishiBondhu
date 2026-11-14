<x-layouts.app :title="__('My Visit Requests')">
    <section class="kb-section">
        <h1>{{ __('My Visit Requests') }}</h1>

        @if (session('status'))
            <p role="status" class="kb-alert kb-alert-success">{{ session('status') }}</p>
        @endif

        @if($requests->isEmpty())
            <p>{{ __('You have not sent any visit requests yet.') }} <a href="{{ route('vets.index') }}">{{ __('Find a veterinarian') }}</a></p>
        @else
            @foreach($requests as $req)
                <article class="kb-card">
                    <h2>{{ __('Veterinarian') }}: {{ $req->veterinarianProfile->user->name }} ({{ strtoupper($req->status) }})</h2>
                    <p><strong>{{ __('Animal') }}:</strong> {{ __(ucfirst($req->animal_type)) }} | <strong>{{ __('Date') }}:</strong> {{ $req->preferred_date->format('d M, Y') }}</p>
                    <p><strong>{{ __('Address') }}:</strong> {{ $req->farm_address }}</p>
                    <p><strong>{{ __('Problem') }}:</strong> {{ $req->problem }}</p>
                    @if($req->vet_note)
                        <p><strong>{{ __('Veterinarian Note') }}:</strong> {{ $req->vet_note }}</p>
                    @endif
                    @if($req->status === 'pending')
                        <form method="POST" action="{{ route('visit-requests.cancel', $req) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="kb-btn kb-btn-danger">{{ __('Cancel Request') }}</button>
                        </form>
                    @endif
                </article>
            @endforeach
            {{ $requests->links() }}
        @endif
    </section>
</x-layouts.app>