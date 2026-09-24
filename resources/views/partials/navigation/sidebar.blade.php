{{-- Expects: $items = [['route','label','icon','active'?], ...], $homeRoute (optional) --}}
<aside class="app-sidebar">
    <a href="{{ isset($homeRoute) ? route($homeRoute) : url('/') }}" class="brand">
        <span class="brand-mark">IW</span>
        <span class="brand-name">{{ config('app.name', 'IWSTGS') }}</span>
    </a>

    <nav class="side-nav">
        @foreach ($items as $item)
            @include('partials.navigation.sidebar-item', ['item' => $item])
        @endforeach
    </nav>

    <div class="sidebar-foot">
        <span style="font-size:12px;color:var(--text-muted);">{{ auth()->user()->name ?? '' }}</span>
        <button type="button" class="sidebar-collapse-btn" data-sidebar-toggle aria-label="Collapse sidebar">
            <span class="material-symbols-outlined" style="font-size:16px">chevron_left</span>
        </button>
    </div>
</aside>
