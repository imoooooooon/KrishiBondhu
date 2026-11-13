<?php

namespace App\Http\Controllers;

use App\Models\CropListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Public crop marketplace (marketplace.index, marketplace.show). Built for F1: browsing published listings.
 * F2 (search and filters), F3 (offers) and F5 (cart and orders) add to these pages later.
 * Only published listings are ever read here; removed ones are excluded by SoftDeletes.
 */
class MarketplaceController extends Controller
{
    /** Published crops, newest first, 12 per page. Only the cover photo is loaded for each card. */
    public function index(): View
    {
        $listings = CropListing::query()
            ->published()
            ->with('coverPhoto')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(CropListing::PER_PAGE);

        return view('marketplace.index', ['listings' => $listings]);
    }

    /**
     * One published listing. Unpublished, removed and unknown listings all give the same 404,
     * so the page never reveals which one it was.
     * ?photo={id} chooses the large photo, so the gallery also works without JavaScript.
     */
    public function show(Request $request, CropListing $listing): View
    {
        Gate::authorize('viewPublic', $listing);

        $listing->load(['photos', 'deliveryOptions']);
        $selected = $listing->photos->firstWhere('id', (int) $request->query('photo')) ?? $listing->photos->first();

        $more = CropListing::query()
            ->published()
            ->whereKeyNot($listing->id)
            ->with('coverPhoto')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('marketplace.show', [
            'listing' => $listing,
            'selectedPhoto' => $selected,
            'moreListings' => $more,
            'isOwner' => $request->user()?->id === $listing->farmer_id,
        ]);
    }
}
