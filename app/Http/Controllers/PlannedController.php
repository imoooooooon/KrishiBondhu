<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Temporary target for routes listed in docs/IA.md that are not built yet.
 *
 * Every planned route is registered and named now, so navigation, links and
 * forms can already use route('name'). GET requests show a "planned page";
 * other methods answer 501 Not Implemented.
 *
 * When you build a feature, point its route at your own controller and keep
 * the same URI and route name, for example:
 *
 *     Route::resource('listings', PlannedController::class)    // before
 *     Route::resource('listings', CropListingController::class) // after
 *
 * Do not add feature logic here.
 */
class PlannedController extends Controller
{
    /** Single-action routes: Route::get('/x', PlannedController::class). */
    public function __invoke(Request $request): Response
    {
        return $this->planned($request);
    }

    /** Resource and named actions: index, create, store, show, edit, update, destroy, ... */
    public function __call($method, $parameters): Response
    {
        return $this->planned(request());
    }

    private function planned(Request $request): Response
    {
        $route = $request->route();
        $name = $route?->getName() ?? 'unnamed';

        return response()->view('planned', [
            'routeName' => $name,
            'title' => Str::headline(str_replace(['.', '-'], ' ', $name)),
            'method' => $request->method(),
            'uri' => '/'.ltrim($route?->uri() ?? '', '/'),
        ], $request->isMethodSafe() ? 200 : 501);
    }
}
