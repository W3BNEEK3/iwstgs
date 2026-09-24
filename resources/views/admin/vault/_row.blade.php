<tr id="vault-row-{{ $item->getId() }}">
    <td><x-ui.badge tone="neutral">{{ str_replace('_', ' ', $item->getDocumentType()->value) }}</x-ui.badge></td>
    <td>{{ $item->getTitle() }}</td>
    <td style="color:var(--text-muted);font-size:var(--text-sm);">{{ \Illuminate\Support\Str::limit($item->toPrimitives()['content'], 100) }}</td>
    <td>
        <x-ui.button severity="destructive" type="button" data-modal-open="remove-vault-{{ $item->getId() }}">Remove</x-ui.button>
        <x-overlays.confirm-modal
            :id="'remove-vault-' . $item->getId()"
            title="Remove this vault item?"
            description="This can't be undone."
            :action="route('admin.projects.vault.destroy', [$projectId, $item->getId()])"
            method="DELETE"
            confirmLabel="Remove"
        />
    </td>
</tr>
