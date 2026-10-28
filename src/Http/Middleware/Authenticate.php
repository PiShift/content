<?php

namespace PiShift\Content\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends guests to the admin's own login page and requires the "content.manage" ability.
 *
 * @internal Not part of the public API; may change in any release.
 */
class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = config('content.admin.guard', 'web');

        if (! $request->user($guard)) {
            return redirect()->guest(route('content.admin.login'));
        }

        abort_unless($request->user($guard)->can('content.manage'), 403);

        return $next($request);
    }
}
