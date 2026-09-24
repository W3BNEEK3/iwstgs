{{-- Public/marketing pages — full-width top nav, footer (design doc §8.1) --}}
@extends('layouts.app')

@section('page')
<div style="display:flex;flex-direction:column;min-height:100vh;">

    <header class="guest-topbar" id="guest-topbar">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">IW</span>
            <span class="brand-name">{{ config('app.name', 'IWSTGS') }}</span>
        </a>

        {{-- Theme toggle — follows prefers-color-scheme on first visit, user choice persists via cookie (design doc §2.3) --}}
        <button
            type="button"
            class="btn-icon guest-theme-toggle"
            id="theme-toggle"
            aria-label="Toggle dark mode"
        >
            <span class="material-symbols-outlined theme-icon-dark" aria-hidden="true">dark_mode</span>
            <span class="material-symbols-outlined theme-icon-light" aria-hidden="true">light_mode</span>
        </button>

        {{-- Mobile hamburger --}}
        <button
            type="button"
            class="btn-icon guest-nav-toggle"
            id="guest-mobile-menu-toggle"
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="guest-mobile-drawer"
            aria-label="Open navigation menu"
        >
            <x-ui.icon name="menu" />
        </button>

        {{-- Desktop nav — inline buttons shown above 760px --}}
        <nav id="guest-nav-menu" class="btn-row guest-nav-menu" aria-label="Site navigation">
            @yield('guest-nav')
        </nav>
    </header>

    {{-- Mobile full-screen drawer --}}
    <div
        id="guest-mobile-drawer"
        class="guest-mobile-drawer"
        role="dialog"
        aria-modal="true"
        aria-label="Navigation menu"
        hidden
    >
        <div class="guest-mobile-drawer-inner">
            <div class="guest-mobile-drawer-header">
                <a href="{{ route('home') }}" class="brand">
                    <span class="brand-mark">IW</span>
                    <span class="brand-name">{{ config('app.name', 'IWSTGS') }}</span>
                </a>
                <button
                    type="button"
                    class="btn-icon"
                    id="guest-mobile-menu-close"
                    aria-label="Close navigation menu"
                >
                    <x-ui.icon name="close" />
                </button>
            </div>

            <nav class="guest-mobile-nav" aria-label="Mobile site navigation">
                @yield('guest-nav')
            </nav>
        </div>
        {{-- Scrim / backdrop --}}
        <div class="guest-mobile-drawer-scrim" id="guest-mobile-drawer-scrim" aria-hidden="true"></div>
    </div>

    <main style="flex:1;">
        @yield('body')
    </main>

    <footer class="guest-footer">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'IWSTGS') }}. All rights reserved.</span>
        <span aria-hidden="true">&middot;</span>
        <a href="{{ route('login') }}" style="color:inherit;text-decoration:none;">Sign in</a>
        <span aria-hidden="true">&middot;</span>
        <a href="{{ route('register') }}" style="color:inherit;text-decoration:none;">Create account</a>
    </footer>
</div>

@push('scripts')
<script>
  (function () {
    var toggle   = document.getElementById('guest-mobile-menu-toggle');
    var close    = document.getElementById('guest-mobile-menu-close');
    var scrim    = document.getElementById('guest-mobile-drawer-scrim');
    var drawer   = document.getElementById('guest-mobile-drawer');
    if (!toggle || !drawer) return;

    function openDrawer() {
      drawer.hidden = false;
      toggle.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
      // Focus the close button for keyboard accessibility
      if (close) close.focus();
    }

    function closeDrawer() {
      drawer.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
      toggle.focus();
    }

    toggle.addEventListener('click', openDrawer);
    if (close)  close.addEventListener('click', closeDrawer);
    if (scrim)  scrim.addEventListener('click', closeDrawer);

    // Close on Escape key (design doc §10.3 — consistent with modal close pattern)
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !drawer.hidden) closeDrawer();
    });

    // Close automatically if the viewport widens past the mobile breakpoint
    var mq = window.matchMedia('(min-width: 760px)');
    mq.addEventListener('change', function (e) {
      if (e.matches && !drawer.hidden) closeDrawer();
    });
  }());
</script>
<script>
  // Theme toggle — design doc §2.3: follow prefers-color-scheme on first visit,
  // explicit toggle persists via cookie so the server can read it on next load.
  (function () {
    var html    = document.documentElement;
    var btn     = document.getElementById('theme-toggle');
    if (!btn) return;

    function getEffectiveTheme() {
      var attr = html.getAttribute('data-theme');
      if (attr === 'dark' || attr === 'light') return attr;
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
      html.setAttribute('data-theme', theme);
      // Cookie survives page loads and is readable server-side (design doc §2.3)
      document.cookie = 'theme=' + theme + ';path=/;max-age=' + (60 * 60 * 24 * 365) + ';SameSite=Lax';
      btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    }

    // Set initial aria-label to match current state
    applyTheme(getEffectiveTheme());

    btn.addEventListener('click', function () {
      var next = getEffectiveTheme() === 'dark' ? 'light' : 'dark';
      applyTheme(next);
    });
  }());
</script>
@endpush

@endsection
