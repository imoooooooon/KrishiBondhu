<x-layouts.app :title="$vet->user->name">
    <section class="kb-section">
        <h1>{{ $vet->user->name }}</h1>
        <p><strong>{{ __('Area') }}:</strong> {{ $vet->upazila }}, {{ $vet->district }}</p>
        <p><strong>{{ __('Address') }}:</strong> {{ $vet->address }}</p>
        @if($vet->experience_years !== null)
            <p><strong>{{ __('Experience') }}:</strong> {{ $vet->experience_years }} {{ __('years') }}</p>
        @endif
        @if($vet->bio)
            <p>{{ $vet->bio }}</p>
        @endif

        <hr>

        @auth
            @if(auth()->user()->hasRole(\App\Models\Role::FARMER))
                <h2>{{ __('Request a Farm Visit') }}</h2>
                @error('veterinarian_profile_id')
                    <p class="kb-error">{{ $message }}</p>
                @enderror

                @if($vet->is_listed)
                    <form method="POST" action="{{ route('vets.visit-requests.store', $vet) }}" class="kb-form">
                        @csrf
                        <div class="kb-field">
                            <label for="animal_type">{{ __('Animal Type') }}</label>
                            <select id="animal_type" name="animal_type" required>
                                <option value="">{{ __('Select animal type') }}</option>
                                @foreach(\App\Http\Controllers\VisitRequestController::ANIMAL_TYPES as $type)
                                    <option value="{{ $type }}" @selected(old('animal_type') === $type)>{{ __(ucfirst($type)) }}</option>
                                @endforeach
                            </select>
                            @error('animal_type') <p class="kb-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="kb-field">
                            <label for="preferred_date">{{ __('Preferred Date') }}</label>
                            <input id="preferred_date" type="date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ date('Y-m-d') }}" required>
                            @error('preferred_date') <p class="kb-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="kb-field">
                            <label for="farm_address">{{ __('Farm Address') }}</label>
                            <input id="farm_address" type="text" name="farm_address" value="{{ old('farm_address') }}" maxlength="255" required>
                            @error('farm_address') <p class="kb-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="kb-field">
                            <label for="problem">{{ __('Problem Description') }}</label>
                            <textarea id="problem" name="problem" rows="3" required>{{ old('problem') }}</textarea>
                            @error('problem') <p class="kb-error">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="kb-btn kb-btn-primary">{{ __('Send Request') }}</button>
                    </form>
                @else
                    <p>{{ __('This veterinarian is not currently accepting visit requests.') }}</p>
                @endif
            @endif
        @else
            <p><a href="{{ route('login') }}">{{ __('Sign in') }}</a> {{ __('to request a farm visit.') }}</p>
        @endauth

        <p><a href="{{ route('vets.index') }}">&larr; {{ __('Back to Veterinarians') }}</a></p>
    </section>
</x-layouts.app>