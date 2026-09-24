@extends('admin.layouts.admin')

@section('title', 'AI Engine — Configuration')

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-md);margin-bottom:var(--sp-2xl);">
    <div>
        <h1 style="margin:0 0 var(--sp-xs);">AI Engine Configuration</h1>
        <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">
            Read-only display of AI system settings. To change feature flag states, go to
            <a href="{{ route('admin.feature-flags.index') }}">Feature Flags</a>.
        </p>
    </div>
    <a href="{{ route('admin.ai-engine.index') }}" style="text-decoration:none;">
        <x-ui.button severity="secondary" icon="arrow_back">Event Log</x-ui.button>
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:var(--sp-xl);">

    {{-- Provider --}}
    <div class="panel">
        <h2 style="margin:0 0 var(--sp-lg);font-size:var(--text-md);display:flex;align-items:center;gap:var(--sp-sm);">
            <span class="material-symbols-outlined" style="font-size:1.1em;">api</span>
            AI Provider
        </h2>
        <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--sp-xs) var(--sp-lg);font-size:var(--text-sm);">
            <dt style="color:var(--text-muted);">Model</dt>
            <dd style="margin:0;font-family:monospace;">{{ $config['provider'] }}</dd>
            <dt style="color:var(--text-muted);">API Key</dt>
            <dd style="margin:0;">
                @if ($config['api_key_set'])
                    <x-ui.badge tone="success">Configured</x-ui.badge>
                @else
                    <x-ui.badge tone="danger">Missing — set CLAUDE_API_KEY in .env</x-ui.badge>
                @endif
            </dd>
        </dl>
    </div>

    {{-- Feature flags --}}
    <div class="panel">
        <h2 style="margin:0 0 var(--sp-lg);font-size:var(--text-md);display:flex;align-items:center;gap:var(--sp-sm);">
            <span class="material-symbols-outlined" style="font-size:1.1em;">toggle_on</span>
            Feature Flags
        </h2>
        <ul style="list-style:none;padding:0;margin:0;display:grid;gap:var(--sp-sm);">
            @foreach ($flagStates as $flag => $enabled)
                <li style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-md);font-size:var(--text-sm);">
                    <code style="font-size:0.85em;">{{ $flag }}</code>
                    <x-ui.badge :tone="$enabled ? 'success' : 'neutral'">{{ $enabled ? 'Enabled' : 'Disabled' }}</x-ui.badge>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Thresholds --}}
    <div class="panel">
        <h2 style="margin:0 0 var(--sp-lg);font-size:var(--text-md);display:flex;align-items:center;gap:var(--sp-sm);">
            <span class="material-symbols-outlined" style="font-size:1.1em;">tune</span>
            Adaptive Thresholds
        </h2>
        <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--sp-xs) var(--sp-lg);font-size:var(--text-sm);">
            <dt style="color:var(--text-muted);">Success streak for CAC escalation</dt>
            <dd style="margin:0;font-family:monospace;">{{ $config['success_streak_threshold'] }} tasks</dd>
            <dt style="color:var(--text-muted);">Failure streak for rank review</dt>
            <dd style="margin:0;font-family:monospace;">{{ $config['failure_streak_threshold'] }} tasks</dd>
            <dt style="color:var(--text-muted);">Habit flag threshold (suggestion)</dt>
            <dd style="margin:0;font-family:monospace;">{{ $config['habit_pattern_threshold'] }} observations</dd>
            <dt style="color:var(--text-muted);">Pass tiers</dt>
            <dd style="margin:0;font-family:monospace;">{{ implode(', ', $config['pass_tiers']) }}</dd>
        </dl>
    </div>

    {{-- CAC priority --}}
    <div class="panel">
        <h2 style="margin:0 0 var(--sp-lg);font-size:var(--text-md);display:flex;align-items:center;gap:var(--sp-sm);">
            <span class="material-symbols-outlined" style="font-size:1.1em;">sort</span>
            CAC Escalation Priority
        </h2>
        <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0 0 var(--sp-md);">
            The order in which CAC dimensions are escalated as a learner demonstrates consistent performance.
        </p>
        <ol style="padding-left:1.2em;margin:0;font-size:var(--text-sm);display:grid;gap:var(--sp-xs);">
            @foreach ($config['cac_priority_order'] as $dimension)
                <li>{{ $dimension }}</li>
            @endforeach
        </ol>
    </div>

</div>
@endsection
