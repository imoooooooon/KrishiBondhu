<x-layouts.app :title="__('Incoming Visit Requests')">
    <section class="kb-section">
        <h1>{{ __('Incoming Visit Requests') }}</h1>

        @if (session('status'))
            <p role="status" class="kb-alert kb-alert-success">{{ session('status') }}</p>
        @endif

        @if($requests->isEmpty())
            <p>{{ __('No visit requests received yet.') }}</p>
        @else
            @foreach($requests as $req)
                <article class="kb-card">
                    <h2>{{ __('Farmer') }}: {{ $req->farmer->name }} ({{ strtoupper($req->status) }})</h2>
                    <p><strong>{{ __('Animal') }}:</strong> {{ __(ucfirst($req->animal_type)) }} | <strong>{{ __('Date') }}:</strong> {{ $req->preferred_date->format('d M, Y') }}</p>
                    <p><strong>{{ __('Address') }}:</strong> {{ $req->farm_address }}</p>
                    <p><strong>{{ __('Problem') }}:</strong> {{ $req->problem }}</p>

                    @if($req->status === 'pending')
                        <form method="POST" action="{{ route('vet.visit-requests.update', $req) }}" class="kb-form">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="vet_note" placeholder="{{ __('Optional note for farmer') }}">
                            <button type="submit" name="status" value="accepted" class="kb-btn kb-btn-primary">{{ __('Accept') }}</button>
                            <button type="submit" name="status" value="declined" class="kb-btn kb-btn-danger">{{ __('Decline') }}</button>
                        </form>
                    @elseif($req->vet_note)
                        <p><strong>{{ __('Your Note') }}:</strong> {{ $req->vet_note }}</p>
                    @endif
                </article>
            @endforeach
            {{ $requests->links() }}
        @endif
    </section>
</x-layouts.app>