{{--
    One published crop on the marketplace (F1). The whole card links to marketplace.show.
    $listing: CropListing with coverPhoto loaded (thumbnail only: small download on mobile data).
--}}
<x-ui.media-card :href="route('marketplace.show', $listing)" :title="$listing->crop_name" class="market-card"
    :image="$listing->coverPhoto?->url('thumb')" :image-alt="''" icon="wheat"
    :badge="__('Harvested :date', ['date' => $listing->harvest_date->translatedFormat('j M')])" badge-tone="harvest">
    <x-slot:meta>
        <span class="market-card__area"><x-ui.icon name="map-pin" size="14" /> {{ $listing->public_area }}</span>
    </x-slot:meta>
    <x-slot:footer>
        <x-ui.price :amount="$listing->price_per_unit" :unit="$listing->unitLabel()" class="market-card__price" />
        <span class="t-caption t-muted t-num">{{ __(':quantity :unit available', ['quantity' => $listing->quantityForDisplay(), 'unit' => $listing->unitLabel()]) }}</span>
    </x-slot:footer>
</x-ui.media-card>
