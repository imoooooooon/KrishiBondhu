{{--
    F1: photo gallery. Works without JavaScript: each small photo is a normal link that reloads the page with
    ?photo={id}. public/js/crop-listings.js swaps the large photo in place instead, without a reload.
    $listing: CropListing with photos. $selected: the photo shown large (or null). $photoLink: fn(photo) => URL.
    Only the large photo uses the "display" size; the small ones use thumbnails, to save mobile data.
--}}
@php
    $total = $listing->photos->count();
    $position = $selected ? $listing->photos->search(fn ($p) => $p->id === $selected->id) + 1 : 0;
@endphp
<div class="listing-gallery" data-gallery>
    <figure class="listing-gallery__main">
        @if ($selected)
            <img src="{{ $selected->url('display') }}" width="{{ $selected->display_width }}" height="{{ $selected->display_height }}"
                alt="{{ __('Photo :number of :crop', ['number' => $position, 'crop' => $listing->crop_name]) }}" data-gallery-main>
        @else
            <div class="media-card__placeholder listing-gallery__placeholder"><x-ui.icon name="image" size="40" stroke="1.5" /></div>
        @endif
        @if ($total > 1)
            <figcaption class="t-caption t-muted" data-gallery-caption aria-live="polite">{{ __('Photo :number of :total', ['number' => $position, 'total' => $total]) }}</figcaption>
        @endif
    </figure>

    @if ($total > 1)
        <ul class="listing-gallery__thumbs" aria-label="{{ __('All photos') }}">
            @foreach ($listing->photos as $photo)
                <li>
                    <a href="{{ $photoLink($photo) }}" class="listing-gallery__thumb" data-gallery-thumb
                        data-src="{{ $photo->url('display') }}" data-width="{{ $photo->display_width }}" data-height="{{ $photo->display_height }}"
                        data-alt="{{ __('Photo :number of :crop', ['number' => $loop->iteration, 'crop' => $listing->crop_name]) }}"
                        data-caption="{{ __('Photo :number of :total', ['number' => $loop->iteration, 'total' => $total]) }}"
                        @if ($selected && $photo->id === $selected->id) aria-current="true" @endif>
                        <img src="{{ $photo->url('thumb') }}" width="{{ $photo->thumbnail_width }}" height="{{ $photo->thumbnail_height }}"
                            alt="{{ __('Show photo :number', ['number' => $loop->iteration]) }}" loading="lazy" decoding="async">
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
