<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgriculturalProducts\DestroyAgriculturalProductRequest;
use App\Http\Requests\AgriculturalProducts\StoreAgriculturalProductRequest;
use App\Http\Requests\AgriculturalProducts\UpdateAgriculturalProductRequest;
use App\Models\AgriculturalProduct;
use App\Services\AgriculturalProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * F25: the seller's own farm-supply products. Routes: routes/modules/shop.php, names seller.products.*.
 * Spec: docs/features/F25-agricultural-product-sales.md.
 *
 * This controller only coordinates. Who may act -> AgriculturalProductPolicy (via Gate and the Form Requests),
 * valid input -> Form Requests, saving rows safely (locks, versions, duplicate forms) -> AgriculturalProductService.
 */
class AgriculturalProductController extends Controller
{
    public const STATUS_FILTERS = ['all', 'published', 'unpublished'];

    public function __construct(private readonly AgriculturalProductService $products) {}

    /** "My products": newest edit first, 12 per page, optional status filter (seller.products.index). */
    public function index(Request $request): View
    {
        Gate::authorize('create', AgriculturalProduct::class); // the workspace needs the seller role

        $status = $request->query('status', 'all');
        $unknownFilter = ! in_array($status, self::STATUS_FILTERS, true);
        if ($unknownFilter) {
            $status = 'all';
        }

        $owned = AgriculturalProduct::query()->ownedBy($request->user());

        $products = (clone $owned)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('id') // same timestamp: always the same order
            ->paginate(AgriculturalProduct::PER_PAGE)
            ->withQueryString();

        // One small grouped query for the numbers on the filter chips.
        $counts = (clone $owned)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('seller.products.index', [
            'products' => $products,
            'status' => $status,
            'unknownFilter' => $unknownFilter,
            'counts' => [
                'all' => (int) $counts->sum(),
                'published' => (int) ($counts['published'] ?? 0),
                'unpublished' => (int) ($counts['unpublished'] ?? 0),
            ],
        ]);
    }

    /** The empty "Add product" form. A new token is made for every new form (seller.products.create). */
    public function create(): View
    {
        Gate::authorize('create', AgriculturalProduct::class);

        return view('seller.products.create', ['submissionToken' => (string) Str::uuid()]);
    }

    /** Validate and publish (seller.products.store). The owner is always the signed-in user, never a form field. */
    public function store(StoreAgriculturalProductRequest $request): RedirectResponse
    {
        [$product, $created] = $this->products->create(
            $request->user(),
            $request->productData(),
            $request->deliveryMethods(),
            $request->validated('submission_token'),
            $request->photo(),
        );

        if ($product->trashed()) {
            return redirect()->route('seller.products.index')
                ->with('product_warning', __('This form was already used for a product that you removed. Nothing new was added. Open “Add product” to start a new one.'));
        }

        return redirect()->route('seller.products.show', $product)->with('status', $created
            ? __('Your product is published. Buyers can see it in the farm supplies shop.')
            : __('This product was already added from your earlier submission. Nothing was added twice.'));
    }

    /** The owner's view of one product, including the private bulk minimum price (seller.products.show). */
    public function show(Request $request, AgriculturalProduct $product): View
    {
        Gate::authorize('view', $product);

        return view('seller.products.show', [
            'product' => $product->load('deliveryOptions'),
            'confirmRemove' => $request->query('confirm') === 'remove',
        ]);
    }

    /** The edit form (seller.products.edit). */
    public function edit(AgriculturalProduct $product): View
    {
        Gate::authorize('update', $product);

        return view('seller.products.edit', ['product' => $product->load('deliveryOptions')]);
    }

    /**
     * Save changes, publish again, or unpublish (seller.products.update). UpdateAgriculturalProductRequest has
     * already checked ownership and the input. An out-of-date version throws AgriculturalProductConflictException (409).
     */
    public function update(UpdateAgriculturalProductRequest $request, AgriculturalProduct $product): RedirectResponse
    {
        $message = match ($request->operation()) {
            'publish' => __('Your product is published again. Buyers can see it.'),
            'unpublish' => __('Your product is hidden from buyers. You can publish it again at any time.'),
            default => __('Changes saved.'),
        };

        if ($request->operation() === 'save') {
            $this->products->update(
                $product,
                $request->version(),
                $request->productData(),
                $request->deliveryMethods(),
                $request->photo(),
                $request->removePhoto(),
                $request->submittedText(),
            );
        } else {
            $this->products->setVisibility($product, $request->version(), $request->operation() === 'publish');
        }

        return redirect()->route('seller.products.show', $product)->with('status', $message);
    }

    /** Remove after the explicit confirmation (seller.products.destroy). Soft delete: the row stays in the database. */
    public function destroy(DestroyAgriculturalProductRequest $request, AgriculturalProduct $product): RedirectResponse
    {
        $this->products->remove($product, $request->version());

        return redirect()->route('seller.products.index')
            ->with('status', __('Product removed. It no longer appears in your products or in the shop.'));
    }
}
