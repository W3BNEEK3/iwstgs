{{-- Admin → Guide sub-navigation. --}}
@php
    $tabs = [
        'admin.guide.index'         => ['Overview', 'insights'],
        'admin.guide.tips'          => ['Tips', 'tips_and_updates'],
        'admin.guide.resources'     => ['Resources', 'menu_book'],
        'admin.guide.announcements' => ['Announcements', 'campaign'],
    ];
@endphp
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="margin:0 0 var(--sp-xs);">Guide (Tiroco)</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-lg);">
        What Tiroco tells learners on its own: nudges from what it notices, tips, outside resources and new-feature announcements.
        Master switches are the <code>guide.ai_nudges</code> and <code>guide.announcements</code> <a href="{{ route('admin.feature-flags.index') }}">feature flags</a>.
    </p>
    <nav class="btn-row" aria-label="Guide sections">
        @foreach ($tabs as $route => [$label, $icon])
            <x-ui.button :href="route($route)" :severity="request()->routeIs($route . '*') ? 'primary' : 'secondary'" :icon="$icon">{{ $label }}</x-ui.button>
        @endforeach
    </nav>
</div>
