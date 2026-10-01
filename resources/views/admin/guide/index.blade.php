@extends('admin.layouts.admin')

@section('title', 'Guide — Overview')

@section('content')
@include('admin.guide._tabs')

<form method="POST" action="{{ route('admin.guide.settings') }}" class="panel" style="margin-bottom:var(--sp-2xl);">
    @csrf
    @method('PUT')
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:var(--sp-lg);flex-wrap:wrap;margin-bottom:var(--sp-lg);">
        <div>
            <h2 style="margin:0 0 var(--sp-xs);font-size:var(--text-md);">Triggers — last {{ $health->days }} days</h2>
            <p style="margin:0;font-size:var(--text-sm);color:var(--text-muted);">
                When Tiroco speaks up. "Unread" counts messages closed within 2 seconds. Cooldown is per learner (0 = once per event).
            </p>
        </div>
        <div style="max-width:220px;">
            <x-forms.text-input name="daily_cap" type="number" min="0" max="20" label="Messages per learner per day" :value="$health->dailyCap" help="Announcements don't count." />
        </div>
    </div>

    <div class="table-frame">
        <table class="data-table">
            <thead>
                <tr><th>On</th><th>Trigger</th><th>Kind</th><th>Cooldown (h)</th><th>Shown</th><th>Helpful</th><th>Not helpful</th><th>Unread</th></tr>
            </thead>
            <tbody>
                @foreach ($health->triggers as $t)
                    <tr>
                        <td>
                            <input type="hidden" name="triggers[{{ $t['key'] }}][enabled]" value="0">
                            <input type="checkbox" name="triggers[{{ $t['key'] }}][enabled]" value="1" @checked($t['enabled']) aria-label="Enable {{ $t['label'] }}">
                        </td>
                        <td>
                            <a href="{{ route('admin.guide.index', ['trigger' => $t['key']]) }}">{{ $t['label'] }}</a>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);"><code>{{ $t['key'] }}</code></div>
                        </td>
                        <td><x-ui.badge tone="neutral">{{ $t['kind'] }}</x-ui.badge></td>
                        <td style="width:120px;">
                            <div class="field" style="margin:0;">
                                <input type="number" name="triggers[{{ $t['key'] }}][cooldown_hours]" value="{{ $t['cooldown_hours'] }}" min="0" max="8760" style="width:96px;" aria-label="Cooldown hours for {{ $t['label'] }}">
                            </div>
                        </td>
                        <td>{{ $t['shown'] }}</td>
                        <td>{{ $t['helpful'] }}@if ($t['helpful'] + $t['not_helpful'] > 0) <span style="color:var(--text-muted);">({{ round(100 * $t['helpful'] / ($t['helpful'] + $t['not_helpful'])) }}%)</span>@endif</td>
                        <td>{{ $t['not_helpful'] }}</td>
                        <td>{{ $t['quick_dismiss'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-ui.button type="submit" severity="primary" icon="save" style="margin-top:var(--sp-lg);">Save trigger settings</x-ui.button>
</form>

<div style="display:flex;align-items:baseline;justify-content:space-between;gap:var(--sp-md);flex-wrap:wrap;margin-bottom:var(--sp-md);">
    <h2 style="margin:0;font-size:var(--text-md);">Recent messages @if ($trigger) <x-ui.badge tone="info">{{ $trigger }}</x-ui.badge> @endif</h2>
    @if ($trigger)
        <a href="{{ route('admin.guide.index') }}" style="font-size:var(--text-sm);">Show all triggers</a>
    @endif
</div>

@if ($health->recent === [])
    <div class="empty-state">
        <x-ui.icon name="forum" :size="32" />
        <p>No messages yet. They appear here as learners use the platform.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>When</th><th>Trigger</th><th>Message</th><th>Written by</th><th>Status</th><th>Rating</th></tr></thead>
            <tbody>
                @foreach ($health->recent as $m)
                    <tr>
                        <td style="white-space:nowrap;font-size:var(--text-sm);">{{ \Illuminate\Support\Carbon::parse($m->createdAt)->diffForHumans() }}</td>
                        <td><code style="font-size:var(--text-xs);">{{ $m->triggerKey }}</code></td>
                        <td style="max-width:420px;"><strong>{{ $m->title }}</strong><div style="font-size:var(--text-sm);color:var(--text-muted);">{{ $m->body }}</div></td>
                        <td><x-ui.badge :tone="$m->generatedBy === 'ai' ? 'info' : 'neutral'">{{ $m->generatedBy }}</x-ui.badge></td>
                        <td>{{ $m->status }}</td>
                        <td>{{ $m->rating === null ? '—' : str_replace('_', ' ', $m->rating) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
