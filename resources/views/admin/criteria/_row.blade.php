<tr id="criterion-{{ $criterion->id() }}">
    <td>{{ ucfirst($criterion->toPrimitives()['complexity_level']) }}</td>
    <td>
        {{ $criterion->toPrimitives()['task_dimension_label'] }}
        <br><span style="color:var(--text-muted);font-size:var(--text-xs);">{{ $criterion->toPrimitives()['parent_dimension_id'] }}</span>
    </td>
    <td>
        {{ $criterion->toPrimitives()['weight'] }}
        <span style="color:var(--text-muted);font-size:var(--text-xs);">(dim {{ $criterion->toPrimitives()['dimension_weight'] }})</span>
    </td>
    <td style="white-space:nowrap;">
        <a href="{{ route('admin.tasks.criteria.edit', [$criterion->taskId(), $criterion->id()]) }}">Edit</a>
        &middot;
        <x-ui.button severity="destructive" type="button" data-modal-open="remove-criterion-{{ $criterion->id() }}">Remove</x-ui.button>
        <x-overlays.confirm-modal
            :id="'remove-criterion-' . $criterion->id()"
            title="Remove this criterion?"
            description="This can't be undone."
            :action="route('admin.criteria.destroy', $criterion->id())"
            method="DELETE"
            confirmLabel="Remove"
        />
    </td>
</tr>
