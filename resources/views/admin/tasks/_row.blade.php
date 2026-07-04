<tr id="task-row-{{ $task->id() }}">
    <td>{{ $task->toPrimitives()['title'] }}</td>
    <td>{{ $task->toPrimitives()['task_type'] }}</td>
    <td>{{ $task->toPrimitives()['domain'] ?? '—' }}</td>
    <td>{{ $task->toPrimitives()['is_cac_runtime_set'] ? 'Runtime' : 'Fixed' }}</td>
    <td>
        <button
            hx-patch="{{ route('admin.scenarios.tasks.publish', [$scenarioId, $task->id()]) }}"
            hx-target="#task-row-{{ $task->id() }}"
            hx-swap="outerHTML">
            {{ $task->isPublished() ? 'Published ✓' : 'Draft' }}
        </button>
    </td>
    <td><a href="{{ route('admin.scenarios.tasks.edit', [$scenarioId, $task->id()]) }}">Edit</a></td>
</tr>
