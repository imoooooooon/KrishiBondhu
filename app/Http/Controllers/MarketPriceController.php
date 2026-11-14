<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarketPrices\SearchMarketPricesRequest;
use App\Models\Market;
use App\Models\Role;
use App\Services\MarketPriceService;
use Illuminate\View\View;

/**
 * F11: the public market price page (market-prices.index). Anyone may read prices; contributors' names are never shown.
 * The price rules (latest, yesterday, tomorrow's estimate, change) live in MarketPriceService.
 * F13 (comparison) and F14 (trends) are separate pages that stay planned.
 */
class MarketPriceController extends Controller
{
    public function __construct(private readonly MarketPriceService $prices) {}

    public function index(SearchMarketPricesRequest $request): View
    {
        $filters = $request->filters();

        // The recent entries only make sense for one product at one market.
        $recent = isset($filters['item'], $filters['market'])
            ? $this->prices->recent($filters['market'], $filters['item'])
            : null;

        return view('market-prices.index', [
            'board' => $this->prices->board($filters),
            'filters' => $filters,
            'recent' => $recent,
            'markets' => Market::options(),
            'recentMarket' => isset($filters['market']) ? Market::find($filters['market']) : null,
            'isContributor' => (bool) $request->user()?->hasRole(Role::PRICE_CONTRIBUTOR),
        ]);
    }
}
