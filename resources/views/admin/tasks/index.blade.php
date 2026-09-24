@extends('admin.layouts.admin')

@section('title', 'Tasks')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <h1>Tasks</h1>
    <x-ui.button severity="primary" :href="route('admin.scenarios.tasks.create', $scenarioId)" icon="add">New Task</x-ui.button>
</div>

@if (empty($tasks))
    <div class="empty-state">
        <x-ui.icon name="task" :size="32" />
        <p>No tasks yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Type</th><th>Domain</th><th>CAC</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($tasks as $task)
                    <tr id="task-row-{{ $task->id() }}">
                        <td>{{ $task->toPrimitives()['title'] }}</td>
                        <td>{{ $task->toPrimitives()['task_type'] }}</td>
                        <td>{{ $task->toPrimitives()['domain'] ?? '—' }}</td>
                        <td>{{ $task->toPrimitives()['is_cac_runtime_set'] ? 'Runtime' : 'Fixed' }}</td>
                        <td>
                            <x-ui.button
                                :severity="$task->isPublished() ? 'primary' : 'secondary'"
                                hx-patch="{{ route('admin.scenarios.tasks.publish', [$scenarioId, $task->id()]) }}"
                                hx-target="#task-row-{{ $task->id() }}"
                                hx-swap="outerHTML"
                            >
                                {{ $task->isPublished() ? 'Published' : 'Draft' }}
                            </x-ui.button>
                        </td>
                        <td>
                            <x-ui.action-menu id="task-actions-{{ $task->id() }}">
                                <a class="action-menu-item" href="{{ route('admin.tasks.criteria.index', $task->id()) }}">
                                    <x-ui.icon name="checklist" :size="18" /> Criteria
                                </a>
                                <a class="action-menu-item" href="{{ route('admin.scenarios.tasks.edit', [$scenarioId, $task->id()]) }}">
                                    <x-ui.icon name="edit" :size="18" /> Edit
                                </a>
                            </x-ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-row-cards">
        @foreach ($tasks as $task)
            @include('admin.tasks._card', ['task' => $task, 'scenarioId' => $scenarioId])
        @endforeach
    </div>
@endif
@endsection
