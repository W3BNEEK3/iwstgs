<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IWSTGS Admin — @yield('title', 'Dashboard')</title>
    <script src="https://unpkg.com/htmx.org@1.9.12" defer></script>
    <script src="https://unpkg.com/hyperscript.org@0.9.12" defer></script>
    {{-- htmx needs the CSRF token on every non-GET request --}}
    <script>
      document.addEventListener('htmx:configRequest', (e) => {
        e.detail.headers['X-CSRF-TOKEN'] =
          document.querySelector('meta[name=csrf-token]').content;
      });
    </script>
</head>
<body>
    <nav>
        <strong>IWSTGS Admin</strong>
        <a href="{{ route('admin.projects.index') }}">Projects</a>
        <a href="{{ route('admin.feature-flags.index') }}">Feature Flags</a>
        {{-- Scenarios | Tasks | Roles arrive in 4b/4c --}}
    </nav>

    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
