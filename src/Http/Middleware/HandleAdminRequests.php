<?php

namespace PiShift\Content\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use PiShift\Content\Admin\Navigation;

/**
 * Inertia middleware of the admin only: the public site keeps its own Blade rendering.
 *
 * @internal Not part of the public API; may change in any release.
 */
class HandleAdminRequests extends Middleware
{
    protected $rootView = 'content::admin';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $user = $request->user(config('content.admin.guard', 'web'));

        return [
            ...parent::share($request),
            'admin' => [
                'name' => config('content.admin.name'),
                'locale' => $locale,
                'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
                'locales' => config('content.admin.locales', ['ar', 'fr']),
                'contentLocales' => config('content.locales', ['ar']),
                'localeUrl' => route('content.admin.locale', ['locale' => '__LOCALE__']),
                'logoutUrl' => route('content.admin.logout'),
                'siteUrl' => url('/'),
            ],
            't' => fn (): array => trans('content::admin'),
            'auth' => [
                'user' => $user ? ['id' => $user->getAuthIdentifier(), 'name' => $user->name ?? $user->email, 'email' => $user->email ?? null] : null,
            ],
            'navigation' => fn (): array => $user ? app(Navigation::class)->forUser($user) : [],
            'flash' => fn (): array => array_filter([
                'success' => $request->session()->get('content.success'),
                'error' => $request->session()->get('content.error'),
            ]),
        ];
    }
}
