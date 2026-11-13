{{--
    F25: the delivery arrangements a seller accepts, plus their notes. $product: AgriculturalProduct with deliveryOptions loaded.
    Used in the product facts and in the "buy" section of the public product page. No fee is shown: that is F4.
--}}
@if ($product->deliveryOptions->isEmpty())
    <span class="t-muted">{{ __('Not given yet') }}</span>
@else
    <ul class="supply-delivery">
        @foreach ($product->deliveryOptions as $option)
            <li><x-ui.icon name="truck" size="16" /> {{ $option->label() }}</li>
        @endforeach
    </ul>
@endif
@if ($product->delivery_notes)
    <p class="t-callout supply-text supply-text--lines supply-delivery__notes">{{ $product->delivery_notes }}</p>
@endif
