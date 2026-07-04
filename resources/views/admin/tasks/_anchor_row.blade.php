<tr id="anchor-{{ $anchor->id() }}">
    <td>{{ $anchor->toPrimitives()['concept_name'] }}</td>
    <td>{{ $anchor->toPrimitives()['is_required'] ? 'Yes' : 'No' }}</td>
    <td>{{ Str::limit($anchor->toPrimitives()['remediation_hint'] ?? '—', 60) }}</td>
    <td>
        <button
            hx-delete="{{ route('admin.tasks.children.remove', [$taskId, 'anchors', $anchor->id()]) }}"
            hx-target="#anchor-{{ $anchor->id() }}"
            hx-swap="outerHTML">
            Remove
        </button>
    </td>
</tr>
