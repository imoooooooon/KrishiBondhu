{{--
    F25: the large product photo in a fixed 16:9 frame, or a calm placeholder when there is none.
    $product: AgriculturalProduct. The URL goes through AgriculturalProductPhotoController, which checks visibility.
--}}
<figure class="supply-photo">
    @if ($product->hasPhoto())
        <img src="{{ $product->photoUrl('display') }}" alt="{{ __('Photo of :name', ['name' => $product->name]) }}"
            width="{{ $product->photo_width }}" height="{{ $product->photo_height }}" decoding="async">
    @else
        <div class="media-card__placeholder supply-photo__placeholder" data-decorative><x-ui.icon name="package" size="48" stroke="1.5" /></div>
        <figcaption class="t-caption t-muted">{{ __('No photo yet') }}</figcaption>
    @endif
</figure>
