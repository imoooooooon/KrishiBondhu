{{-- F1: a listing's visibility as text (never colour alone). $listing: CropListing. --}}
@if ($listing->isPublished())
    <x-ui.badge tone="success" icon="eye">{{ __('Published') }}</x-ui.badge>
@else
    <x-ui.badge tone="warning" icon="triangle-alert">{{ __('Hidden from buyers') }}</x-ui.badge>
@endif
