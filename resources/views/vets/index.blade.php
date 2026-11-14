<x-layouts.app :title="__('Veterinary Doctors')">
    <section class="kb-section">
        <h1>{{ __('Veterinary Doctors') }}</h1>

        <form method="GET" action="{{ route('vets.index') }}" class="kb-filter-bar">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name or upazila') }}">
            <input type="text" name="district" value="{{ request('district') }}" placeholder="{{ __('District') }}">
            <select name="animal">
                <option value="">{{ __('All Animals') }}</option>
                <option value="cattle" @selected(request('animal') === 'cattle')>{{ __('Cattle') }}</option>
                <option value="goats" @selected(request('animal') === 'goats')>{{ __('Goats') }}</option>
                <option value="poultry" @selected(request('animal') === 'poultry')>{{ __('Poultry') }}</option>
            </select>
            <button type="submit" class="kb-btn kb-btn-primary">{{ __('Search') }}</button>
            @if(request()->hasAny(['search', 'district', 'animal']))
                <a href="{{ route('vets.index') }}" class="kb-btn">{{ __('Reset') }}</a>
            @endif
        </form>

        @if($vets->isEmpty())
            <p>{{ __('No veterinarians found.') }}</p>
        @else
            <div class="kb-grid">
                @foreach($vets as $vet)
                    <article class="kb-card">
                        <h2>{{ $vet->user->name }}</h2>
                        <p><strong>{{ __('Area') }}:</strong> {{ $vet->upazila }}, {{ $vet->district }}</p>
                        <p><strong>{{ __('Address') }}:</strong> {{ $vet->address }}</p>
                        @if($vet->experience_years !== null)
                            <p><strong>{{ __('Experience') }}:</strong> {{ $vet->experience_years }} {{ __('years') }}</p>
                        @endif
                        <p>
                            <strong>{{ __('Treats') }}:</strong>
                            {{ collect([
                                $vet->treats_cattle ? __('Cattle') : null,
                                $vet->treats_goats ? __('Goats') : null,
                                $vet->treats_poultry ? __('Poultry') : null,
                            ])->filter()->implode(', ') ?: __('General') }}
                        </p>
                        <a href="{{ route('vets.show', $vet) }}">{{ __('View Profile & Request Visit') }}</a>
                    </article>
                @endforeach
            </div>
            {{ $vets->links() }}
        @endif
    </section>
</x-layouts.app>