@php($adminLocale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ $adminLocale }}" dir="{{ $adminLocale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (config('content.admin.assets') === 'prebuilt')
        {{-- The package's own build, published to public/vendor/content (never the dev server). --}}
        {{ \Illuminate\Support\Facades\Vite::useBuildDirectory('vendor/content')->useHotFile(storage_path('framework/content-admin.hot'))->withEntryPoints(['resources/admin/standalone.tsx']) }}
    @else
        @viteReactRefresh
        @vite(config('content.admin.vite'))
    @endif
    <x-inertia::head>
        <title>{{ config('content.admin.name') }}</title>
    </x-inertia::head>
</head>
<body class="antialiased">
    <x-inertia::app />
</body>
</html>
