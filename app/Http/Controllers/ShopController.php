<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgriculturalProducts\SearchShopRequest;
use App\Models\AgriculturalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * F25: the public farm-supplies shop (shop.index, shop.show). Routes: routes/modules/shop.php.
 * Only published products are ever read here; removed ones are excluded by SoftDeletes.
 * The private lowest bulk price is never passed to these views' text (see agricultural-products/_facts).
 *
 * Buying (cart, checkout, orders: F5/F4) and bulk offers (F3) are other features that are not built yet,
 * so the product page explains that instead of showing working buttons.
 */
class ShopController extends Controller
{
    /** Published products, newest first, 12 per page, with optional filters that stay in the page links. */
    public function index(SearchShopRequest $request): View
    {
        $filters = $request->filters();

        $products = AgriculturalProduct::query()
            ->published()
            ->search($filters)
            ->orderByDesc('published_at')
            ->orderByDesc('id') // same time: always the same order
            ->paginate(AgriculturalProduct::PER_PAGE)
            ->withQueryString();

        return view('shop.index', ['products' => $products, 'filters' => $filters]);
    }

    /** One published product. Unpublished, removed and unknown products all give the same 404. */
    public function show(Request $request, AgriculturalProduct $product): View
    {
        Gate::authorize('viewPublic', $product);

        $more = AgriculturalProduct::query()
            ->published()
            ->where('category', $product->category)
            ->whereKeyNot($product->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('shop.show', [
            'product' => $product->load('deliveryOptions'),
            'moreProducts' => $more,
            'isOwner' => $request->user()?->id === $product->seller_id,
        ]);
    }
}
