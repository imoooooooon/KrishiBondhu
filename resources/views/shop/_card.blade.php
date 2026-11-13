{{--
    F25: one product card on the public shop. $product: AgriculturalProduct (published).
    Shows the area, fixed price, stock and whether bulk offers are accepted — never the private lowest bulk price.
--}}
<x-ui.media-card :href="route('shop.show', $product)" :title="$product->name" class="supply-text"
    :image="$product->photoUrl('thumb')" icon="package"
    :badge="$product->isOutOfStock() ? __('Out of stock') : null" badge-tone="danger">
    <x-slot:meta>
        @if ($product->public_area)
            <span class="supply-text supply-inline-icon"><x-ui.icon name="map-pin" size="14" /> {{ $product->public_area }}</span>
        @endif
        <span>{{ $product->categoryLabel() }}</span>
        @if ($product->brand)<span class="supply-text">{{ $product->brand }}</span>@endif
    </x-slot:meta>
    <x-slot:footer>
        <x-ui.price :amount="$product->unit_price" :unit="$product->unitLabel()" class="supply-card__price" />
        @if ($product->acceptsBulkOffers())
            <x-ui.badge tone="harvest" icon="hand-coins">{{ __('Bulk offers') }}</x-ui.badge>
        @endif
    </x-slot:footer>
</x-ui.media-card>
