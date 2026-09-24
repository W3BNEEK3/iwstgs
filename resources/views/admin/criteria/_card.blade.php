<div class="mobile-row-card" id="criterion-card-{{ $criterion->id() }}" style="cursor:default;">
    <div class="mobile-row-top">
        <span>
            <span class="mobile-row-title">{{ $criterion->toPrimitives()['task_dimension_label'] }}</span>
            <span class="mobile-row-sub">{{ ucfirst($criterion->toPrimitives()['complexity_level']) }} &middot; {{ $criterion->toPrimitives()['parent_dimension_id'] }}</span>
        </span>
    </div>
    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:var(--sp-sm) 0;">Weight {{ $criterion->toPrimitives()['weight'] }} (dim {{ $criterion->toPrimitives()['dimension_weight'] }})</p>
    <div style="display:flex;gap:var(--sp-sm);">
        <x-ui.button severity="secondary" :href="route('admin.tasks.criteria.edit', [$criterion->taskId(), $criterion->id()])" icon="edit">Edit</x-ui.button>
        <x-ui.button severity="destructive" type="button" data-modal-open="remove-criterion-card-{{ $criterion->id() }}">Remove</x-ui.button>
        <x-overlays.confirm-modal
            :id="'remove-criterion-card-' . $criterion->id()"
            title="Remove this criterion?"
            description="This can't be undone."
            :action="route('admin.criteria.destroy', $criterion->id())"
            method="DELETE"
            confirmLabel="Remove"
        />
    </div>
</div>
