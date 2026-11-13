{{--
    F25: the details of one product as label/value rows.
    $product: AgriculturalProduct. $showPrivate: true ONLY on the owner's pages; it adds the private
    lowest bulk price. Public shop pages include this without $showPrivate, so the minimum never reaches them.
    All seller-written text is printed with {{ }}, which escapes HTML (no <script> can run from a product).
--}}
@php $showPrivate = $showPrivate ?? false; @endphp
<dl class="supply-facts">
    <div>
        <dt>{{ __('Category') }}</dt>
        <dd>{{ $product->categoryLabel() }}</dd>
    </div>
    @if ($product->brand)
        <div>
            <dt>{{ __('Brand') }}</dt>
            <dd class="supply-text">{{ $product->brand }}</dd>
        </div>
    @endif
    <div>
        <dt>{{ __('Fixed price') }}</dt>
        <dd>
            <x-ui.price :amount="$product->unit_price" :unit="$product->unitLabel()" />
            <span class="t-caption t-muted supply-facts__note">{{ __('For small purchases at this price, with no negotiation.') }}</span>
        </dd>
    </div>
    <div>
        <dt>{{ __('Stock') }}</dt>
        <dd>@include('agricultural-products._stock-badge')</dd>
    </div>
    <div>
        <dt>{{ __('Bulk offers') }}</dt>
        <dd>
            @if ($product->acceptsBulkOffers())
                {{ __('Accepted from :quantity :unit', ['quantity' => $product->bulkMinQuantityForDisplay(), 'unit' => $product->unitLabel()]) }}
                @if ($showPrivate)
                    <span class="supply-private">
                        <x-ui.icon name="shield-check" size="16" />
                        <span>{{ __('Your lowest bulk price:') }} <x-ui.price :amount="$product->bulk_min_unit_price" :unit="$product->unitLabel()" /></span>
                    </span>
                    <span class="t-caption t-muted supply-facts__note">{{ __('Private. Buyers never see this price.') }}</span>
                @endif
            @else
                {{ __('Not accepted. Every order uses the fixed price.') }}
            @endif
        </dd>
    </div>
    <div>
        <dt>{{ __('Area') }}</dt>
        <dd class="supply-text">
            @if ($product->public_area)
                <span class="supply-inline-icon"><x-ui.icon name="map-pin" size="16" /> {{ $product->public_area }}</span>
            @else
                <span class="t-muted">{{ __('Not given yet') }}</span>
            @endif
        </dd>
    </div>
    <div>
        <dt>{{ __('Delivery') }}</dt>
        <dd>@include('agricultural-products._delivery')</dd>
    </div>
    <div>
        <dt>{{ __('Description') }}</dt>
        <dd class="supply-text supply-text--lines">{{ $product->description }}</dd>
    </div>
</dl>
