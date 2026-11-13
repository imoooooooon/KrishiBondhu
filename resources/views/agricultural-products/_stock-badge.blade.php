{{-- F25: stock as text (never colour alone). Zero stock cannot be bought (F5 checks this again when it adds the cart). --}}
@if ($product->isOutOfStock())
    <x-ui.badge tone="danger" icon="circle-alert">{{ __('Out of stock') }}</x-ui.badge>
@else
    <x-ui.badge tone="info" icon="package">{{ __(':quantity :unit in stock', ['quantity' => $product->stockForDisplay(), 'unit' => $product->unitLabel()]) }}</x-ui.badge>
@endif
