<tr id="deliverable-{{ $deliverable->id() }}">
    <td>{{ $deliverable->toPrimitives()['type'] }}</td>
    <td>{{ $deliverable->toPrimitives()['label'] }}</td>
    <td>{{ $deliverable->toPrimitives()['is_required'] ? 'Yes' : 'No' }}</td>
    <td>
        <button
            hx-delete="{{ route('admin.tasks.children.remove', [$taskId, 'deliverables', $deliverable->id()]) }}"
            hx-target="#deliverable-{{ $deliverable->id() }}"
            hx-swap="outerHTML">
            Remove
        </button>
    </td>
</tr>
