<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's language on every web request (F24, IA.md §1.5: one URL for both languages).
 * Signed-in users: the language saved on their account. Guests: the language chosen in this session.
 * Otherwise the default from config/app.php (APP_LOCALE).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale');

        if (in_array($locale, config('app.supported_locales'), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
