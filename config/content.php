<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The first locale is the default: "required" fields must be filled in it.
    | When a translatable value is missing in the current locale, the
    | fallbacks are tried in order.
    |
    */

    'locales' => ['ar', 'fr', 'en'],

    'fallbacks' => [
        'fr' => ['ar'],
        'en' => ['fr', 'ar'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery
    |--------------------------------------------------------------------------
    |
    | Section and global types are PHP classes in the project. Every class
    | found in these directories that extends the matching base class is
    | registered automatically.
    |
    */

    'discover' => [
        'sections' => ['path' => app_path('Content/Sections'), 'namespace' => 'App\\Content\\Sections'],
        'globals' => ['path' => app_path('Content/Globals'), 'namespace' => 'App\\Content\\Globals'],
        'collections' => ['path' => app_path('Content/Collections'), 'namespace' => 'App\\Content\\Collections'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    // Blade view prefix of section views: "content.sections.cards".
    'section_views' => 'content.sections',

    // Page templates the admin can choose from: key => [label, view].
    'templates' => [
        'default' => ['label' => 'Default', 'view' => 'content::templates.default'],
    ],

    // Layout extended by the default template; it must @yield('content').
    'layout' => 'layouts.app',

    // CSS classes for each section style option. Sections receive them as $classes.
    'style_classes' => [
        'background' => [
            'default' => '',
            'muted' => 'bg-gray-50',
            'brand' => 'bg-gray-900 text-white',
            'dark' => 'bg-black text-white',
        ],
        'spacing' => [
            'sm' => 'py-10',
            'md' => 'py-16',
            'lg' => 'py-24',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | Pages created in the admin are served by a fallback route, so they never
    | shadow the application's own routes.
    |
    */

    'routes' => [
        'pages' => true,
        'preview_prefix' => 'content-preview',
        'preview_minutes' => 120,
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    |
    | The admin is a React + Inertia app. "assets" picks how it is loaded:
    | - "prebuilt": the package's own build, published with
    |   `php artisan vendor:publish --tag=content-assets` (no React in the project);
    | - "vite": the project's Vite builds it from the "vite" entries (needed for
    |   project admin pages, see README).
    |
    */

    'admin' => [
        'path' => env('CONTENT_ADMIN_PATH', 'admin'),
        'name' => env('CONTENT_ADMIN_NAME', env('APP_NAME', 'Admin')),
        'guard' => 'web',
        'locales' => ['ar', 'fr'],
        'assets' => env('CONTENT_ADMIN_ASSETS', 'prebuilt'),
        'vite' => ['resources/admin/app.tsx'],
        'middleware' => ['web'],

        // The project's look in the admin: an accent colour (#rrggbb; hover and light shades
        // are derived from it) and a logo (URL or path in public/), shown on the login page
        // and in the sidebar. Without them: the package's green and the site name's first letter.
        'brand' => [
            'accent' => env('CONTENT_ADMIN_ACCENT'),
            'logo' => env('CONTENT_ADMIN_LOGO'),
        ],
    ],

    // Pages the application's own routes depend on: they can't be deleted from the admin.
    'protected_pages' => [],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    'media' => [
        'disk' => 'public',
        'directory' => 'content',
        // Max upload size in kilobytes.
        'max_size' => 20480,
        // SVG is left out on purpose: an uploaded SVG can carry scripts.
        'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'zip', 'mp4', 'webm', 'mp3'],
    ],

];
