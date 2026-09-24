<div class="mobile-row-card" id="vault-card-{{ $item->getId() }}" style="cursor:default;">
    <div class="mobile-row-top">
        <span>
            <span class="mobile-row-title">{{ $item->getTitle() }}</span>
            <span class="mobile-row-sub">{{ str_replace('_', ' ', $item->getDocumentType()->value) }}</span>
        </span>
    </div>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:var(--sp-sm) 0;">{{ \Illuminate\Support\Str::limit($item->toPrimitives()['content'], 100) }}</p>
    <x-ui.button severity="destructive" type="button" data-modal-open="remove-vault-card-{{ $item->getId() }}">Remove</x-ui.button>
    <x-overlays.confirm-modal
        :id="'remove-vault-card-' . $item->getId()"
        title="Remove this vault item?"
        description="This can't be undone."
        :action="route('admin.projects.vault.destroy', [$projectId, $item->getId()])"
        method="DELETE"
        confirmLabel="Remove"
    />
</div>
