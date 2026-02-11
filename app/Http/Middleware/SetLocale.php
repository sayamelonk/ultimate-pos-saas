<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = array_keys(config('app.available_locales', ['id' => 'Bahasa Indonesia', 'en' => 'English']));

        // Priority: 1. Session, 2. User preference, 3. Browser, 4. Default
        $locale = session('locale');

        if (! $locale && auth()->check()) {
            $locale = auth()->user()->locale;
        }

        if (! $locale) {
            $locale = $request->getPreferredLanguage($availableLocales);
        }

        // Ensure locale is valid
        if (! in_array($locale, $availableLocales)) {
            $locale = config('app.locale', 'id');
        }

        App::setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
