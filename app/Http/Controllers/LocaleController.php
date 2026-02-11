<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $availableLocales = array_keys(config('app.available_locales', []));

        if (! in_array($locale, $availableLocales)) {
            return back()->with('error', __('app.invalid_language'));
        }

        // Store in session
        session(['locale' => $locale]);
        App::setLocale($locale);

        // Update user preference if logged in
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        return back()->with('success', __('app.language_changed'));
    }
}
