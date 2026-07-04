<tr id="cac-variant-{{ $variant->id() }}">
    <td>{{ $variant->toPrimitives()['complexity_level'] }}</td>
    <td>{{ Str::limit($variant->toPrimitives()['scenario_text'], 80) }}</td>
    <td>
        <button
            hx-delete="{{ route('admin.tasks.children.remove', [$taskId, 'cacVariants', $variant->id()]) }}"
            hx-target="#cac-variant-{{ $variant->id() }}"
            hx-swap="outerHTML">
            Remove
        </button>
    </td>
</tr>
