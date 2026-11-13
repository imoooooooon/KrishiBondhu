{{--
    Signed-in home (route: dashboard). Basic version: the account and its roles.
    IA.md §3: each role will get a card linking into its workspace as the features are built.
--}}
<x-layouts.app :title="__('Dashboard')">
    <div class="container">
        <x-ui.page-header :title="__('Hello, :name', ['name' => $user->name])" :subtitle="__('This is your Krishi Bondhu home.')">
            <x-slot:actions>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" size="md" icon="log-out">{{ __('Log out') }}</x-ui.button>
                </form>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid-2">
            <x-ui.card>
                <x-slot:title>{{ __('Your account') }}</x-slot:title>
                <dl class="stack stack-sm">
                    <div class="cluster cluster--between"><dt class="t-muted">{{ __('Mobile number') }}</dt><dd class="t-num">{{ $user->phone }}</dd></div>
                    <div class="cluster cluster--between"><dt class="t-muted">{{ __('Email') }}</dt><dd>{{ $user->email ?? __('Not added') }}</dd></div>
                    <div class="cluster cluster--between"><dt class="t-muted">{{ __('Language') }}</dt><dd>{{ $user->locale === 'bn' ? 'বাংলা' : 'English' }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <x-slot:title>{{ __('Your roles') }}</x-slot:title>
                <div class="cluster">
                    @foreach ($user->roles as $role)
                        <x-ui.badge tone="success" icon="badge-check">{{ __($role->name) }}</x-ui.badge>
                    @endforeach
                </div>
                <p class="t-callout t-muted mt-4">{{ __('Tools for each role will appear here as they are built.') }}</p>
            </x-ui.card>

            @if ($user->hasRole(\App\Models\Role::FARMER))
                {{-- F1: crop listings (farmer workspace) --}}
                <x-ui.card>
                    <x-slot:title>{{ __('Sell your crops') }}</x-slot:title>
                    <p class="t-callout t-muted mb-4">{{ __('Publish a crop lot with photos and a price per unit. Buyers see it on the marketplace.') }}</p>
                    <div class="cluster">
                        <x-ui.button size="md" icon="plus" :href="route('farmer.listings.create')">{{ __('Add crop listing') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="list" :href="route('farmer.listings.index')">{{ __('My crop listings') }}</x-ui.button>
                    </div>
                </x-ui.card>

                {{-- F6: digital cattle profiles (private herd records) --}}
                <x-ui.card>
                    <x-slot:title>{{ __('My cattle') }}</x-slot:title>
                    <p class="t-callout t-muted mb-4">{{ __('Keep each animal’s photo, KB ID, age, parents and health history in one private place.') }}</p>
                    <div class="cluster">
                        <x-ui.button size="md" icon="cow-head" :href="route('cattle.index')">{{ __('My cattle') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="plus" :href="route('cattle.create')">{{ __('Register cattle') }}</x-ui.button>
                    </div>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-layouts.app>
