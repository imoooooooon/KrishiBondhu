<?php

namespace App\Http\Controllers\Contributor;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketPrices\StoreMarketPriceRequest;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Services\MarketPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * F11: a market price contributor's own price entries. Routes: routes/modules/market-prices.php,
 * names contributor.prices.*. Spec: docs/features/F11-market-price-tracking.md.
 *
 * This controller only coordinates. Who may act -> MarketPricePolicy (via Gate and the Form Requests),
 * valid input -> Form Requests, saving rows (duplicates, history) -> MarketPriceService.
 */
class MarketPriceController extends Controller
{
    public function __construct(private readonly MarketPriceService $prices) {}

    /** "My prices": the contributor's own entries, latest change first, 15 per page (contributor.prices.index). */
    public function index(Request $request): View
    {
        Gate::authorize('create', MarketPrice::class); // the workspace needs the contributor role

        $prices = MarketPrice::query()
            ->recordedBy($request->user())
            ->with('market') // one query for all markets on the page, not one per row
            ->orderByDesc('updated_at')
            ->orderByDesc('id') // same time: always the same order
            ->paginate(MarketPrice::PER_PAGE);

        return view('contributor.prices.index', ['prices' => $prices]);
    }

    /** The empty "Record a price" form (contributor.prices.create). */
    public function create(): View
    {
        Gate::authorize('create', MarketPrice::class);

        return view('contributor.prices.create', ['markets' => Market::options()]);
    }

    /** Validate and record (contributor.prices.store). The contributor is always the signed-in user, never a form field. */
    public function store(StoreMarketPriceRequest $request): RedirectResponse
    {
        [, $created] = $this->prices->record($request->user(), $request->priceData());

        return redirect()->route('contributor.prices.index')->with('status', $created
            ? __('Price recorded. Thank you: buyers and farmers can now see it.')
            : __('This price was already recorded from your earlier submission. Nothing was added twice.'));
    }
}
