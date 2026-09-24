<tr id="deliverable-{{ $deliverable->id() }}">
    <td>{{ $deliverable->toPrimitives()['type'] }}</td>
    <td>{{ $deliverable->toPrimitives()['label'] }}</td>
    <td>{{ $deliverable->toPrimitives()['is_required'] ? 'Yes' : 'No' }}</td>
    <td>
        <x-ui.button severity="destructive" type="button" data-modal-open="remove-deliverable-{{ $deliverable->id() }}">Remove</x-ui.button>
        <x-overlays.confirm-modal
            :id="'remove-deliverable-' . $deliverable->id()"
            title="Remove this deliverable?"
            description="This can't be undone."
            :action="route('admin.tasks.children.remove', [$taskId, 'deliverables', $deliverable->id()])"
            :hx-target="'#deliverable-' . $deliverable->id()"
            confirmLabel="Remove"
        />
    </td>
</tr>
