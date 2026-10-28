<?php

namespace PiShift\Content\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin has its own interface language, independent of the public site's.
 */
class SetAdminLocale
{
    public const SESSION_KEY = 'content.admin_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('content.admin.locales', ['ar', 'fr']);
        $locale = $request->session()->get(self::SESSION_KEY);

        app()->setLocale(in_array($locale, $locales, true) ? $locale : $locales[0]);

        return $next($request);
    }
}
