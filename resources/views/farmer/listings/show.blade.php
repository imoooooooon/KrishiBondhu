{{--
    The owner's page for one listing (route farmer.listings.show, owner only). F1.
    Controller: Farmer\CropListingController@show. Every form here sends the version this page was opened with.
    Removing: the "Remove listing" link reloads this page with ?confirm=remove, which shows a confirmation box.
    Nothing is changed by opening a link (GET); only the final button (DELETE) removes. No JavaScript needed.
--}}
<x-layouts.app :title="$listing->crop_name">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/crop-listings.css').'?v='.@filemtime(public_path('css/crop-listings.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="$listing->crop_name" class="listing-text">
            <x-slot:breadcrumbs>
                <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                <x-ui.icon name="chevron-right" size="14" />
                <a href="{{ route('farmer.listings.index') }}">{{ __('My crop listings') }}</a>
            </x-slot:breadcrumbs>
            <x-slot:actions>
                @if ($listing->isPublished())
                    <x-ui.button size="md" variant="secondary" icon="external-link" :href="route('marketplace.show', $listing)">{{ __('View public listing') }}</x-ui.button>
                @endif
                <x-ui.button size="md" icon="pencil" :href="route('farmer.listings.edit', $listing)">{{ __('Edit') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($errors->has('operation') || $errors->has('version') || $errors->has('confirm_remove'))
            <x-ui.alert tone="danger" role="alert" class="mb-6" :title="__('This was not done')">
                <ul class="listing-error-list">
                    @foreach ([...$errors->get('operation'), ...$errors->get('version'), ...$errors->get('confirm_remove')] as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @unless ($listing->isPublished())
            <x-ui.alert tone="warning" class="mb-6" :title="__('Hidden from buyers')">
                {{ __('Only you can see this listing. Publish it again when the crop is available.') }}
            </x-ui.alert>
        @endunless

        <div class="listing-layout">
            @include('crop-listings._gallery', [
                'selected' => $selectedPhoto,
                'photoLink' => fn ($photo) => route('farmer.listings.show', [$listing, 'photo' => $photo->id]),
            ])

            <div class="stack">
                <x-ui.card class="stack">
                    <div class="cluster cluster--between">
                        @include('crop-listings._status-badge')
                        <span class="t-caption t-muted">{{ __('Updated :date', ['date' => $listing->updated_at->translatedFormat('j M Y')]) }}</span>
                    </div>
                    @include('crop-listings._facts')
                </x-ui.card>

                <x-ui.card class="stack stack-sm">
                    <h2 class="t-title-3">{{ __('Visibility') }}</h2>
                    @if ($listing->isPublished())
                        <p class="t-callout t-muted">{{ __('Buyers can see this listing. Hide it when the crop is not available for a while.') }}</p>
                        <form method="POST" action="{{ route('farmer.listings.update', $listing) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="operation" value="unpublish">
                            <input type="hidden" name="version" value="{{ $listing->version }}">
                            <x-ui.button type="submit" size="md" variant="secondary">{{ __('Unpublish') }}</x-ui.button>
                        </form>
                    @else
                        <p class="t-callout t-muted">{{ __('The listing is checked again before it is shown to buyers.') }}</p>
                        <form method="POST" action="{{ route('farmer.listings.update', $listing) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="operation" value="publish">
                            <input type="hidden" name="version" value="{{ $listing->version }}">
                            <x-ui.button type="submit" size="md" icon="eye">{{ __('Publish again') }}</x-ui.button>
                        </form>
                    @endif
                    <p class="t-caption t-muted">
                        {{ __('Listed on :date', ['date' => $listing->created_at->translatedFormat('j M Y')]) }}
                        @if ($listing->published_at) · {{ __('First published :date', ['date' => $listing->published_at->translatedFormat('j M Y')]) }} @endif
                    </p>
                </x-ui.card>

                @if ($confirmRemove)
                    <section class="card listing-danger stack stack-sm" id="remove-confirmation" aria-labelledby="remove-title" tabindex="-1">
                        <h2 class="t-title-3" id="remove-title">{{ __('Remove this crop listing?') }}</h2>
                        <p class="t-callout listing-text">
                            <strong>{{ $listing->crop_name }}</strong> —
                            {{ __('It will disappear from your listings and the marketplace. This screen cannot restore it.') }}
                        </p>
                        <form method="POST" action="{{ route('farmer.listings.destroy', $listing) }}" class="cluster">
                            @csrf @method('DELETE')
                            <input type="hidden" name="confirm_remove" value="1">
                            <input type="hidden" name="version" value="{{ $listing->version }}">
                            <x-ui.button type="submit" size="md" variant="danger" icon="trash-2">{{ __('Remove listing') }}</x-ui.button>
                            <x-ui.button size="md" variant="tertiary" :href="route('farmer.listings.show', $listing)">{{ __('Cancel') }}</x-ui.button>
                        </form>
                    </section>
                @else
                    <section class="card listing-danger stack stack-sm" aria-labelledby="danger-title">
                        <h2 class="t-title-3" id="danger-title">{{ __('Remove listing') }}</h2>
                        <p class="t-callout t-muted">{{ __('Removing hides the listing everywhere. To hide it only for a while, use Unpublish.') }}</p>
                        <div><x-ui.button size="md" variant="danger" icon="trash-2" :href="route('farmer.listings.show', [$listing, 'confirm' => 'remove']).'#remove-confirmation'">{{ __('Remove listing') }}</x-ui.button></div>
                    </section>
                @endif
            </div>
        </div>
    </div>

    <x-slot:scripts><script src="{{ asset('js/crop-listings.js').'?v='.@filemtime(public_path('js/crop-listings.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
