<?php

namespace PiShift\Content\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PiShift\Content\Http\Middleware\SetAdminLocale;

/**
 * @internal Not part of the public API; may change in any release.
 */
class LocaleController
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('content.admin.locales', ['ar', 'fr']), true), 404);

        $request->session()->put(SetAdminLocale::SESSION_KEY, $locale);

        return back();
    }
}
