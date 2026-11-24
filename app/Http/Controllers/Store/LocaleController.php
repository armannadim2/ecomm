<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** Remember the visitor's language and go back to the page they were on. */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('app.available_locales')), 404);

        $request->session()->put('locale', $locale);

        return redirect()->back(fallback: route('store.home'));
    }
}
