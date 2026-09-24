@extends('admin.layouts.admin')

@section('title', 'Learner Reporting')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <h1>Learner Progress</h1>
</div>

@if (empty($overviews))
    <div class="empty-state">
        <x-ui.icon name="groups" :size="32" />
        <p>No learners yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Learner</th><th>Entry Category</th><th>Rank</th><th>Failure Streak</th><th>Mismatch</th><th>Sessions</th></tr></thead>
            <tbody>
                @foreach ($overviews as $o)
                    <tr>
                        <td>{{ $o->fullname }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($o->entryCategory)) }}</td>
                        <td><x-ui.badge tone="info">{{ $o->rankTier }}-{{ $o->rankLevel }}</x-ui.badge></td>
                        <td>
                            @if ($o->failureStreak > 0)
                                <x-ui.badge tone="warning">{{ $o->failureStreak }}</x-ui.badge>
                            @else
                                0
                            @endif
                        </td>
                        <td>
                            @if ($o->mismatchFlagActive)
                                <x-ui.badge tone="error">Flagged</x-ui.badge>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $o->sessionCount }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-row-cards">
        @foreach ($overviews as $o)
            <div class="mobile-row-card" style="cursor:default;">
                <div class="mobile-row-top">
                    <span class="mobile-row-title">{{ $o->fullname }}</span>
                    <span class="mobile-row-sub">{{ str_replace('_', ' ', ucfirst($o->entryCategory)) }}</span>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:var(--sp-sm);">
                    <x-ui.badge tone="info">{{ $o->rankTier }}-{{ $o->rankLevel }}</x-ui.badge>
                    @if ($o->failureStreak > 0)
                        <x-ui.badge tone="warning">{{ $o->failureStreak }} failures</x-ui.badge>
                    @endif
                    @if ($o->mismatchFlagActive)
                        <x-ui.badge tone="error">Mismatch flagged</x-ui.badge>
                    @endif
                    <x-ui.badge tone="neutral">{{ $o->sessionCount }} sessions</x-ui.badge>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
