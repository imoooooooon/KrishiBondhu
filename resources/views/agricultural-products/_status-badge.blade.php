{{-- F25: a product's visibility as text (never colour alone). $product: AgriculturalProduct. --}}
@if ($product->isPublished())
    <x-ui.badge tone="success" icon="eye">{{ __('Published') }}</x-ui.badge>
@else
    <x-ui.badge tone="warning" icon="triangle-alert">{{ __('Hidden from buyers') }}</x-ui.badge>
@endif
