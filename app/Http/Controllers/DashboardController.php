<?php

namespace App\Http\Controllers;

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
        return view('dashboard', [
            'user' => $request->user()->load('roles'),
        ]);
    }
}
