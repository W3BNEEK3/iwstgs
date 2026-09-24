@extends('admin.layouts.admin')

@section('title', 'Task Performance')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <h1>Task Performance</h1>
</div>

@if (empty($performance))
    <div class="empty-state">
        <x-ui.icon name="fact_check" :size="32" />
        <p>No evaluated submissions yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Task</th><th>Attempts</th><th>Pass Rate</th><th>Tier Distribution</th></tr></thead>
            <tbody>
                @foreach ($performance as $p)
                    <tr>
                        <td>{{ $p->taskTitle }}</td>
                        <td>{{ $p->attemptCount }}</td>
                        <td>
                            <x-ui.badge :tone="$p->passRate >= 70 ? 'success' : ($p->passRate >= 40 ? 'warning' : 'error')">{{ $p->passRate }}%</x-ui.badge>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                @foreach ($p->tierCounts as $tier => $count)
                                    <x-ui.badge tone="neutral">{{ ucfirst($tier) }}: {{ $count }}</x-ui.badge>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-row-cards">
        @foreach ($performance as $p)
            <div class="mobile-row-card" style="cursor:default;">
                <div class="mobile-row-top">
                    <span class="mobile-row-title">{{ $p->taskTitle }}</span>
                    <span class="mobile-row-sub">{{ $p->attemptCount }} attempts</span>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:var(--sp-sm);">
                    <x-ui.badge :tone="$p->passRate >= 70 ? 'success' : ($p->passRate >= 40 ? 'warning' : 'error')">{{ $p->passRate }}% pass</x-ui.badge>
                    @foreach ($p->tierCounts as $tier => $count)
                        <x-ui.badge tone="neutral">{{ ucfirst($tier) }}: {{ $count }}</x-ui.badge>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
