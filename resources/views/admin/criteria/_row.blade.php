<tr id="criterion-{{ $criterion->id() }}">
    <td>{{ $criterion->toPrimitives()['complexity_level'] }}</td>
    <td>
        {{ $criterion->toPrimitives()['parent_dimension_id'] }}<br>
        <small>({{ $criterion->toPrimitives()['task_dimension_label'] }})</small>
    </td>
    <td>
        {{ $criterion->toPrimitives()['weight'] }} 
        <small>(Dim: {{ $criterion->toPrimitives()['dimension_weight'] }})</small>
    </td>
    <td>
        <a href="{{ route('admin.tasks.criteria.edit', [$criterion->taskId(), $criterion->id()]) }}">Edit</a>
        <button
            hx-delete="{{ route('admin.criteria.destroy', $criterion->id()) }}"
            hx-target="#criterion-{{ $criterion->id() }}"
            hx-swap="outerHTML">
            Remove
        </button>
    </td>
</tr>
