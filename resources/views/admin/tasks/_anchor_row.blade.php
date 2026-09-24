<tr id="anchor-{{ $anchor->id() }}">
    <td>{{ $anchor->toPrimitives()['concept_name'] }}</td>
    <td>{{ $anchor->toPrimitives()['is_required'] ? 'Yes' : 'No' }}</td>
    <td>{{ \Illuminate\Support\Str::limit($anchor->toPrimitives()['remediation_hint'] ?? '—', 60) }}</td>
    <td>
        <x-ui.button severity="destructive" type="button" data-modal-open="remove-anchor-{{ $anchor->id() }}">Remove</x-ui.button>
        <x-overlays.confirm-modal
            :id="'remove-anchor-' . $anchor->id()"
            title="Remove this knowledge anchor?"
            description="This can't be undone."
            :action="route('admin.tasks.children.remove', [$taskId, 'anchors', $anchor->id()])"
            :hx-target="'#anchor-' . $anchor->id()"
            confirmLabel="Remove"
        />
    </td>
</tr>
