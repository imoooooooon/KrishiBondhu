<x-layouts.app :title="__('Veterinarian Profile')">
    <section class="kb-section">
        <h1>{{ __('Veterinarian Profile') }}</h1>

        @if (session('status'))
            <p role="status" class="kb-alert kb-alert-success">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('vet.profile.update') }}" class="kb-form">
            @csrf
            @method('PATCH')

            <div class="kb-field">
                <label for="district">{{ __('District') }}</label>
                <input id="district" type="text" name="district" value="{{ old('district', $profile->district) }}" required>
                @error('district') <p class="kb-error">{{ $message }}</p> @enderror
            </div>

            <div class="kb-field">
                <label for="upazila">{{ __('Upazila') }}</label>
                <input id="upazila" type="text" name="upazila" value="{{ old('upazila', $profile->upazila) }}" required>
                @error('upazila') <p class="kb-error">{{ $message }}</p> @enderror
            </div>

            <div class="kb-field">
                <label for="address">{{ __('Chamber or Area Address') }}</label>
                <input id="address" type="text" name="address" value="{{ old('address', $profile->address) }}" required>
                @error('address') <p class="kb-error">{{ $message }}</p> @enderror
            </div>

            <fieldset class="kb-fieldset">
                <legend>{{ __('Animals Treated') }}</legend>
                <label>
                    <input type="checkbox" name="treats_cattle" value="1" @checked(old('treats_cattle', $profile->treats_cattle))>
                    {{ __('Cattle') }}
                </label>
                <label>
                    <input type="checkbox" name="treats_goats" value="1" @checked(old('treats_goats', $profile->treats_goats))>
                    {{ __('Goats') }}
                </label>
                <label>
                    <input type="checkbox" name="treats_poultry" value="1" @checked(old('treats_poultry', $profile->treats_poultry))>
                    {{ __('Poultry') }}
                </label>
            </fieldset>

            <div class="kb-field">
                <label for="experience_years">{{ __('Experience (Years)') }}</label>
                <input id="experience_years" type="number" name="experience_years" min="0" max="60" value="{{ old('experience_years', $profile->experience_years) }}">
                @error('experience_years') <p class="kb-error">{{ $message }}</p> @enderror
            </div>

            <div class="kb-field">
                <label for="bio">{{ __('Bio') }}</label>
                <textarea id="bio" name="bio" rows="3">{{ old('bio', $profile->bio) }}</textarea>
                @error('bio') <p class="kb-error">{{ $message }}</p> @enderror
            </div>

            <div class="kb-field">
                <label>
                    <input type="checkbox" name="is_listed" value="1" @checked(old('is_listed', $profile->is_listed ?? true))>
                    {{ __('List my profile publicly for farm visit requests') }}
                </label>
            </div>

            <button type="submit" class="kb-btn kb-btn-primary">{{ __('Save Profile') }}</button>
        </form>
    </section>
</x-layouts.app>