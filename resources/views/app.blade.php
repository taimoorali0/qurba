<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($seo = \App\Support\Seo::for(request()))
        <title inertia>{{ $seo['title'] }}</title>
        <meta name="description" content="{{ $seo['description'] }}">
        <link rel="canonical" href="{{ $seo['url'] }}">
        @if ($seo['noindex'])
        <meta name="robots" content="noindex">
        @endif
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'Qurba') }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $seo['url'] }}">
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">

        @routes
        {{-- ===== QURBA HEAD — START ===== --}}
    <meta name="theme-color" content="#0B3B2D">
    <link rel="icon" type="image/png" sizes="32x32" href="/brand/qurba-icon-32.png">
    <link rel="apple-touch-icon" href="/brand/qurba-icon-180.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Qurba">
    {{-- ===== QURBA HEAD — END ===== --}}    @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
