<?php

namespace PiShift\Content\Tests\Fixtures;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public site language from the session, as a site's own middleware would set it.
 */
class SessionLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->session()->get('locale'), config('content.locales'), true)) {
            app()->setLocale($request->session()->get('locale'));
        }

        return $next($request);
    }
}
