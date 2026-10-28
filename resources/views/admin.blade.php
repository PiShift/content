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
    @viteReactRefresh
    @vite(config('content.admin.vite'))
    <x-inertia::head>
        <title>{{ config('content.admin.name') }}</title>
    </x-inertia::head>
</head>
<body class="antialiased">
    <x-inertia::app />
</body>
</html>
