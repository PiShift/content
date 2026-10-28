<?php

namespace PiShift\Content\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PiShift\Content\Http\Middleware\SetAdminLocale;

class AuthController
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user(config('content.admin.guard', 'web'))) {
            return redirect()->route('content.admin.home');
        }

        return Inertia::render('Auth/Login', [
            'loginUrl' => route('content.admin.login.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey), 'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60)]),
            ]);
        }

        if (! Auth::guard(config('content.admin.guard', 'web'))->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);

        $adminLocale = $request->session()->get(SetAdminLocale::SESSION_KEY);
        $request->session()->regenerate();
        $request->session()->put(SetAdminLocale::SESSION_KEY, $adminLocale);

        return redirect()->intended(route('content.admin.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard(config('content.admin.guard', 'web'))->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('content.admin.login');
    }
}
