<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check route/query parameter
        $locale = $request->route('locale') ?? $request->query('locale');

        // 2. Check cookie
        if (! is_string($locale) || ! in_array($locale, ['en', 'ar'], true)) {
            $locale = $request->cookie('locale');
        }

        // 3. Check session
        if ((! is_string($locale) || ! in_array($locale, ['en', 'ar'], true)) && $request->hasSession()) {
            $locale = $request->session()->get('locale');
        }

        // 4. Fallback to app default
        if (! is_string($locale) || ! in_array($locale, ['en', 'ar'], true)) {
            $locale = config('app.locale', 'ar');
        }

        // Always validate the locale
        if (! in_array($locale, ['en', 'ar'], true)) {
            $locale = 'ar';
        }

        // Store in session for persistence
        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        // Store in cookie for long-term persistence (6 months)
        Cookie::queue(Cookie::make('locale', $locale, 43200, '/', null, false, false));
        
        // Apply to app
        config(['app.locale' => $locale]);
        app()->setLocale($locale);

        return $next($request);
    }
}
