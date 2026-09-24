<tr id="cac-variant-{{ $variant->id() }}">
    <td>{{ ucfirst($variant->toPrimitives()['complexity_level']) }}</td>
    <td>{{ \Illuminate\Support\Str::limit($variant->toPrimitives()['scenario_text'], 80) }}</td>
    <td>
        <x-ui.button severity="destructive" type="button" data-modal-open="remove-cac-variant-{{ $variant->id() }}">Remove</x-ui.button>
        <x-overlays.confirm-modal
            :id="'remove-cac-variant-' . $variant->id()"
            title="Remove this CAC variant?"
            description="This can't be undone."
            :action="route('admin.tasks.children.remove', [$taskId, 'cacVariants', $variant->id()])"
            :swap-target="'#cac-variant-' . $variant->id()"
            confirmLabel="Remove"
        />
    </td>
</tr>
