@extends('admin.layouts.admin')

@section('title', 'Rubric Criteria')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.scenarios.tasks.edit', [$scenarioId, $taskId]) }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to task
    </a>
</p>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <h1>Rubric Criteria</h1>
    <x-ui.button severity="primary" :href="route('admin.tasks.criteria.create', $taskId)" icon="add">Add Criterion</x-ui.button>
</div>

@if (empty($criteria))
    <div class="empty-state">
        <x-ui.icon name="checklist" :size="32" />
        <p>No criteria authored yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Complexity</th><th>Dimension</th><th>Weight</th><th></th></tr></thead>
            <tbody>
                @foreach ($criteria as $criterion)
                    @include('admin.criteria._row', ['criterion' => $criterion])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-row-cards">
        @foreach ($criteria as $criterion)
            @include('admin.criteria._card', ['criterion' => $criterion])
        @endforeach
    </div>
@endif
@endsection
