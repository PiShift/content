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
    | Media
    |--------------------------------------------------------------------------
    */

    'media' => [
        'disk' => 'public',
        'directory' => 'content',
    ],

];
