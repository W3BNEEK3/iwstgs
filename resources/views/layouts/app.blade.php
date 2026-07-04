<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IWSTGS')</title>

    {{-- HTMX: handles partial page updates via server-rendered HTML fragments.
         No JavaScript written by us — HTMX attributes on HTML elements drive
         all dynamic behaviour. Works perfectly with Laravel's Blade views because
         the server always returns complete HTML, never JSON. --}}
    <script src="https://unpkg.com/htmx.org@1.9.12" defer></script>

    {{-- _HyperScript: handles lightweight client-side behaviour (toggling classes,
         showing/hiding elements, simple animations). Syntax lives on the HTML element
         itself as an _ attribute. No separate JS files needed. --}}
    <script src="https://unpkg.com/hyperscript.org@0.9.12" defer></script>

    <link rel="stylesheet" href="{{ asset('css/ui.css') }}">

    @stack('styles')
</head>
<body hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'>
    @yield('content')

    @stack('scripts')
</body>
</html>
