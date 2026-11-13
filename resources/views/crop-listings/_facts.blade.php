{{--
    F1: the details of one crop listing as label/value rows. Used by the owner page, the public page and the
    "changed elsewhere" page, so buyers and farmers always read the same wording.
    $listing: CropListing with photos and deliveryOptions loaded.
    All farmer-written text is printed with {{ }}, which escapes HTML (no <script> can run from a listing).
--}}
<dl class="listing-facts">
    <div>
        <dt>{{ __('Price per :unit', ['unit' => $listing->unitLabel()]) }}</dt>
        <dd><x-ui.price :amount="$listing->price_per_unit" :unit="$listing->unitLabel()" /></dd>
    </div>
    <div>
        <dt>{{ __('Quantity offered') }}</dt>
        <dd class="t-num">{{ $listing->quantityForDisplay() }} {{ $listing->unitLabel() }}</dd>
    </div>
    <div>
        <dt>{{ __('Harvest date') }}</dt>
        <dd>{{ $listing->harvest_date->translatedFormat('j F Y') }}</dd>
    </div>
    <div>
        <dt>{{ __('Area') }}</dt>
        <dd class="listing-text">{{ $listing->public_area }}</dd>
    </div>
    <div>
        <dt>{{ __('Quality') }}</dt>
        <dd>
            <span class="listing-text listing-text--lines">{{ $listing->quality_description }}</span>
            <span class="t-caption t-muted listing-facts__note">{{ __('Described by the farmer') }}</span>
        </dd>
    </div>
    <div>
        <dt>{{ __('Delivery') }}</dt>
        <dd>
            <ul class="listing-delivery">
                @foreach ($listing->deliveryOptions as $option)
                    <li><x-ui.icon name="truck" size="16" /> {{ $option->label() }}</li>
                @endforeach
            </ul>
            @if ($listing->delivery_notes)
                <p class="t-callout listing-text listing-text--lines mt-2">{{ $listing->delivery_notes }}</p>
            @endif
        </dd>
    </div>
</dl>
