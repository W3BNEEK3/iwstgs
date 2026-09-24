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
@include('admin.tasks._card', ['task' => $task, 'scenarioId' => $scenarioId, 'oob' => true])
