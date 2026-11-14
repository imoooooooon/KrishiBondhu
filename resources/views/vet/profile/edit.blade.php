<x-layouts.app :title="__('Veterinarian Profile')">
    <div class="container">
        <x-ui.page-header :title="__('Veterinarian Profile')" :subtitle="__('Set your chamber location, animals treated, and public listing status.')">
            <x-slot:breadcrumbs><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" variant="secondary" icon="list" :href="route('vet.visit-requests.index')">{{ __('Incoming Visit Requests') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert tone="success" role="status" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ route('vet.profile.update') }}" class="stack">
                @csrf
                @method('PATCH')

                <div class="grid-2">
                    <x-ui.field name="district" :label="__('District')" :value="$profile->district" required />
                    <x-ui.field name="upazila" :label="__('Upazila')" :value="$profile->upazila" required />
                </div>

                <x-ui.field name="address" :label="__('Chamber or Area Address')" :value="$profile->address" required />

                <fieldset class="field">
                    <legend class="field__label">{{ __('Animals Treated') }}</legend>
                    <div class="cluster">
                        <x-ui.checkbox name="treats_cattle" value="1" :checked="(bool) $profile->treats_cattle" :label="__('Cattle')" />
                        <x-ui.checkbox name="treats_goats" value="1" :checked="(bool) $profile->treats_goats" :label="__('Goats')" />
                        <x-ui.checkbox name="treats_poultry" value="1" :checked="(bool) $profile->treats_poultry" :label="__('Poultry')" />
                    </div>
                </fieldset>

                <x-ui.field name="experience_years" type="number" :label="__('Experience (Years)')" :value="$profile->experience_years" min="0" max="60" optional />

                <x-ui.textarea name="bio" :label="__('Bio')" :value="$profile->bio" rows="3" />

                <x-ui.checkbox name="is_listed" value="1" :checked="(bool) ($profile->is_listed ?? true)" :label="__('List my profile publicly for farm visit requests')" />

                <div>
                    <x-ui.button type="submit" icon="check">{{ __('Save Profile') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>