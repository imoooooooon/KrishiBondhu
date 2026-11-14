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

                {{-- F18 & F19: veterinary visit requests, treatment history and reminders --}}
                <x-ui.card>
                    <x-slot:title>{{ __('Veterinary care & treatments') }}</x-slot:title>
                    <p class="t-callout t-muted mb-4">{{ __('Find veterinarians for farm visits, keep treatment notes, and check follow-up reminders.') }}</p>
                    <div class="cluster">
                        <x-ui.button size="md" icon="stethoscope" :href="route('vets.index')">{{ __('Find a veterinarian') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="list" :href="route('visit-requests.index')">{{ __('My Visit Requests') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="heart-pulse" :href="route('treatments.index')">{{ __('Treatment History') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="bell" :href="route('reminders.index')">{{ __('Follow-up Reminders') }}</x-ui.button>
                    </div>
                </x-ui.card>

                {{-- F15: soil-based fertilizer guidance (AI guidance from the farmer's own observations) --}}
                <x-ui.card>
                    <x-slot:title>{{ __('Soil and fertilizer guidance') }}</x-slot:title>
                    <p class="t-callout t-muted mb-4">{{ __('Describe your crop and soil to get general soil-care and fertilizer guidance. A photo is optional.') }}</p>
                    <div class="cluster">
                        <x-ui.button size="md" icon="sprout" :href="route('guidance.soil.create')">{{ __('Get soil guidance') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="list" :href="route('guidance.soil.index')">{{ __('My soil guidance') }}</x-ui.button>
                    </div>
                </x-ui.card>
            @endif

            @if ($user->hasRole(\App\Models\Role::VETERINARIAN))
                {{-- F18: veterinarian profile and incoming farm visit requests --}}
                <x-ui.card>
                    <x-slot:title>{{ __('Veterinary practice') }}</x-slot:title>
                    <p class="t-callout t-muted mb-4">{{ __('Manage your public doctor profile and respond to farm visit requests from farmers.') }}</p>
                    <div class="cluster">
                        <x-ui.button size="md" icon="stethoscope" :href="route('vet.visit-requests.index')">{{ __('Incoming Visit Requests') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="user" :href="route('vet.profile.edit')">{{ __('Veterinarian Profile') }}</x-ui.button>
                    </div>
                </x-ui.card>
            @endif

            @if ($user->hasRole(\App\Models\Role::SELLER))
                {{-- F25: agricultural products (seller workspace) --}}
                <x-ui.card>
                    <x-slot:title>{{ __('Sell farm supplies') }}</x-slot:title>
                    <p class="t-callout t-muted mb-4">{{ __('Add fertilizer, pesticide, seed or tools with a fixed price and stock. Buyers see them in Farm supplies.') }}</p>
                    <div class="cluster">
                        <x-ui.button size="md" icon="package" :href="route('seller.products.index')">{{ __('My products') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="plus" :href="route('seller.products.create')">{{ __('Add product') }}</x-ui.button>
                    </div>
                </x-ui.card>
            @endif

            @if ($user->hasRole(\App\Models\Role::PRICE_CONTRIBUTOR))
                {{-- F11: market price contributor workspace, with the latest own entries --}}
                <x-ui.card>
                    <x-slot:title>{{ __('Market prices') }}</x-slot:title>
                    <p class="t-callout t-muted mb-4">{{ __('Record today’s prices from your market. You can correct or withdraw your entries for 7 days.') }}</p>
                    @if ($recentPrices->isNotEmpty())
                        <div class="list-group mb-4">
                            @foreach ($recentPrices as $price)
                                <x-ui.list-row :title="$price->itemName().' · ৳ '.\App\Models\CropListing::localiseDigits((string) $price->unit_price).' / '.$price->unitLabel()"
                                    :subtitle="$price->market->displayName().' · '.$price->priceDateForDisplay().' · '.($price->is_estimate ? __('Estimate') : $price->sessionLabel())"
                                    :href="$price->canStillChange() ? route('contributor.prices.edit', $price) : null" />
                            @endforeach
                        </div>
                    @endif
                    <div class="cluster">
                        <x-ui.button size="md" icon="plus" :href="route('contributor.prices.create')">{{ __('Record a price') }}</x-ui.button>
                        <x-ui.button size="md" variant="secondary" icon="list" :href="route('contributor.prices.index')">{{ __('My market prices') }}</x-ui.button>
                    </div>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-layouts.app>
