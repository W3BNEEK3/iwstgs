{{-- Public/marketing pages — full-width top nav, footer (design doc §8.1) --}}
@extends('layouts.app')

@section('page')
<div style="display:flex;flex-direction:column;min-height:100vh;" x-data
     @keydown.escape.window="$store.nav.closeDrawer()"
     x-init="window.matchMedia('(min-width: 760px)').addEventListener('change', (e) => { if (e.matches) $store.nav.closeDrawer() })">

    <header class="guest-topbar" id="guest-topbar">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">AR</span>
            <span class="brand-name">{{ config('app.name', 'Areyna') }}</span>
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
            :aria-expanded="$store.nav.drawerOpen"
            @click="$store.nav.openDrawer(); $nextTick(() => $refs.drawerClose.focus())"
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
        x-show="$store.nav.drawerOpen"
        x-cloak
    >
        <div class="guest-mobile-drawer-inner">
            <div class="guest-mobile-drawer-header">
                <a href="{{ route('home') }}" class="brand">
                    <span class="brand-mark">AR</span>
                    <span class="brand-name">{{ config('app.name', 'Areyna') }}</span>
                </a>
                <button
                    type="button"
                    class="btn-icon"
                    id="guest-mobile-menu-close"
                    aria-label="Close navigation menu"
                    x-ref="drawerClose"
                    @click="$store.nav.closeDrawer()"
                >
                    <x-ui.icon name="close" />
                </button>
            </div>

            <nav class="guest-mobile-nav" aria-label="Mobile site navigation">
                @yield('guest-nav')
            </nav>
        </div>
        {{-- Scrim / backdrop --}}
        <div class="guest-mobile-drawer-scrim" id="guest-mobile-drawer-scrim" aria-hidden="true" @click="$store.nav.closeDrawer()"></div>
    </div>

    <main style="flex:1;">
        @yield('body')
    </main>

    <footer class="guest-footer">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'Areyna') }}. All rights reserved.</span>
        <span aria-hidden="true">&middot;</span>
        <a href="{{ route('login') }}" style="color:inherit;text-decoration:none;">Sign in</a>
        <span aria-hidden="true">&middot;</span>
        <a href="{{ route('register') }}" style="color:inherit;text-decoration:none;">Create account</a>
    </footer>
</div>

@push('scripts')
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
