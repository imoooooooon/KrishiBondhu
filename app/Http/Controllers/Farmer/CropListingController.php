<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CropListings\DestroyCropListingRequest;
use App\Http\Requests\CropListings\StoreCropListingRequest;
use App\Http\Requests\CropListings\UpdateCropListingRequest;
use App\Models\CropListing;
use App\Services\CropListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * F1: the farmer's own crop listings. Routes: routes/modules/marketplace.php, names farmer.listings.*.
 * Spec: docs/features/F1-crop-listings.md.
 *
 * This controller only coordinates. Who may act -> CropListingPolicy (via Gate and the Form Requests),
 * valid input -> Form Requests, saving rows + photo files safely -> CropListingService.
 */
class CropListingController extends Controller
{
    public const STATUS_FILTERS = ['all', 'published', 'unpublished'];

    public function __construct(private readonly CropListingService $listings) {}

    /** "My crop listings": newest edit first, 12 per page, optional status filter (farmer.listings.index). */
    public function index(Request $request): View
    {
        Gate::authorize('create', CropListing::class); // the workspace needs the Farmer role

        $status = $request->query('status', 'all');
        $unknownFilter = ! in_array($status, self::STATUS_FILTERS, true);
        if ($unknownFilter) {
            $status = 'all';
        }

        $owned = $request->user()->cropListings();

        $listings = (clone $owned)
            ->with('coverPhoto')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('id') // same timestamp: always the same order
            ->paginate(CropListing::PER_PAGE)
            ->withQueryString();

        // One small grouped query for the numbers on the filter chips.
        $counts = (clone $owned)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('farmer.listings.index', [
            'listings' => $listings,
            'status' => $status,
            'unknownFilter' => $unknownFilter,
            'counts' => [
                'all' => (int) $counts->sum(),
                'published' => (int) ($counts['published'] ?? 0),
                'unpublished' => (int) ($counts['unpublished'] ?? 0),
            ],
        ]);
    }

    /** The empty "Add crop listing" form. A new token is made for every new form (farmer.listings.create). */
    public function create(): View
    {
        Gate::authorize('create', CropListing::class);

        return view('farmer.listings.create', ['submissionToken' => (string) Str::uuid()]);
    }

    /** Validate and publish (farmer.listings.store). The owner is always the signed-in user, never a form field. */
    public function store(StoreCropListingRequest $request): RedirectResponse
    {
        [$listing, $created] = $this->listings->create(
            $request->user(),
            $request->listingData(),
            $request->deliveryMethods(),
            $request->validated('submission_token'),
            $request->uploads(),
        );

        if ($listing->trashed()) {
            return redirect()->route('farmer.listings.index')
                ->with('listing_warning', __('This form was already used for a listing that you removed. Nothing new was published. Open “Add crop listing” to start a new one.'));
        }

        return redirect()->route('farmer.listings.show', $listing)->with('status', $created
            ? __('Your listing is published. Buyers can see it on the marketplace now.')
            : __('This listing was already published from your earlier submission. Nothing was published twice.'));
    }

    /** The owner's view of one listing, with visibility and remove controls (farmer.listings.show). */
    public function show(Request $request, CropListing $listing): View
    {
        Gate::authorize('view', $listing);

        $listing->load(['photos', 'deliveryOptions']);

        return view('farmer.listings.show', [
            'listing' => $listing,
            // ?photo={id} picks the large photo, so the gallery works without JavaScript.
            'selectedPhoto' => $listing->photos->firstWhere('id', (int) $request->query('photo')) ?? $listing->photos->first(),
            'confirmRemove' => $request->query('confirm') === 'remove',
        ]);
    }

    /** The edit form (farmer.listings.edit). */
    public function edit(CropListing $listing): View
    {
        Gate::authorize('update', $listing);

        return view('farmer.listings.edit', ['listing' => $listing->load(['photos', 'deliveryOptions'])]);
    }

    /**
     * Save changes, publish again, or unpublish (farmer.listings.update). UpdateCropListingRequest has already
     * checked ownership and the input. An out-of-date version throws CropListingConflictException (a 409 page).
     */
    public function update(UpdateCropListingRequest $request, CropListing $listing): RedirectResponse
    {
        $message = match ($request->operation()) {
            'publish' => __('Your listing is published again. Buyers can see it.'),
            'unpublish' => __('Your listing is hidden from buyers. You can publish it again at any time.'),
            default => __('Changes saved.'),
        };

        if ($request->operation() === 'save') {
            $this->listings->update(
                $listing,
                $request->version(),
                $request->listingData(),
                $request->deliveryMethods(),
                $request->removePhotoIds(),
                $request->uploads(),
                $request->submittedText(),
            );
        } else {
            $this->listings->setVisibility($listing, $request->version(), $request->operation() === 'publish');
        }

        return redirect()->route('farmer.listings.show', $listing)->with('status', $message);
    }

    /** Remove after the explicit confirmation (farmer.listings.destroy). Soft delete: the rows stay in the database. */
    public function destroy(DestroyCropListingRequest $request, CropListing $listing): RedirectResponse
    {
        $this->listings->remove($listing, $request->version());

        return redirect()->route('farmer.listings.index')
            ->with('status', __('Listing removed. It no longer appears in your listings or on the marketplace.'));
    }
}
