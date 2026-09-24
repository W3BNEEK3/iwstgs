{{-- Off-canvas mobile nav — same $items as the sidebar (design doc §9) --}}
<div x-data @keydown.escape.window="$store.nav.closeDrawer()">
    <div class="mobile-drawer-overlay" :class="{ 'is-open': $store.nav.drawerOpen }" @click="$store.nav.closeDrawer()"></div>
    <aside id="app-mobile-drawer" class="mobile-drawer" :class="{ 'is-open': $store.nav.drawerOpen }" @click="if ($event.target.closest('a')) $store.nav.closeDrawer()">
        <a href="{{ isset($homeRoute) ? route($homeRoute) : url('/') }}" class="brand">
            <span class="brand-mark">AR</span>
            <span class="brand-name">{{ config('app.name', 'Areyna') }}</span>
        </a>
        <nav class="side-nav">
            @foreach ($items as $item)
                @include('partials.navigation.sidebar-item', ['item' => $item])
            @endforeach
        </nav>
    </aside>
</div>
