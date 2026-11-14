<?php

namespace App\Http\Controllers;

use App\Models\MarketPrice;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Signed-in home (route dashboard). For now it shows the account and its roles.
 * Role cards linking into each workspace (IA.md §3) are added as those features are built.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('roles');

        return view('dashboard', [
            'user' => $user,
            // F11: a market price contributor's 5 latest entries (withdrawn ones are hidden by the soft delete).
            'recentPrices' => $user->hasRole(Role::PRICE_CONTRIBUTOR)
                ? MarketPrice::query()->recordedBy($user)->with('market')->orderByDesc('updated_at')->orderByDesc('id')->limit(5)->get()
                : collect(),
        ]);
    }
}
