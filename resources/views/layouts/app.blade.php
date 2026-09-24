<!DOCTYPE html>
<html lang="en" @if (request()->cookie('theme')) data-theme="{{ request()->cookie('theme') }}" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Areyna'))</title>

    {{-- Material Symbols via CDN — confirmed decision (design doc §6, §22.4); revisit
         self-hosting at Phase 11, not before. --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">

    {{-- --font-ui in tokens.css has named Instrument Sans since the design system was
         drafted, but nothing ever linked it — every page has been silently falling
         back to system-ui. --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;650;700&family=JetBrains+Mono:wght@400;500&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @include('partials.feedback.page-loading-bar')

    @yield('page')

    @include('partials.feedback.toast-stack')
    @include('partials.feedback.network-snackbar')

    @stack('scripts')
</body>
</html>
