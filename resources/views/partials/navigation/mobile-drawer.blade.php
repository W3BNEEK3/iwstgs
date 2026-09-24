{{-- Off-canvas mobile nav — same $items as the sidebar (design doc §9) --}}
<div class="mobile-drawer-overlay" data-mobile-drawer-overlay></div>
<aside class="mobile-drawer" data-mobile-drawer>
    <a href="{{ isset($homeRoute) ? route($homeRoute) : url('/') }}" class="brand">
        <span class="brand-mark">IW</span>
        <span class="brand-name">{{ config('app.name', 'IWSTGS') }}</span>
    </a>
    <nav class="side-nav">
        @foreach ($items as $item)
            @include('partials.navigation.sidebar-item', ['item' => $item])
        @endforeach
    </nav>
</aside>
