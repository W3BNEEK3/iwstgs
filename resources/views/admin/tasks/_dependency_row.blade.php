<li id="dependency-{{ $dependency->id() }}" style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-md);border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--sp-sm) var(--sp-md);">
    <span>Prerequisite: <code>{{ $dependency->prerequisiteTaskId() }}</code></span>
    <x-ui.button severity="destructive" type="button" data-modal-open="remove-dependency-{{ $dependency->id() }}">Remove</x-ui.button>
    <x-overlays.confirm-modal
        :id="'remove-dependency-' . $dependency->id()"
        title="Remove this dependency?"
        description="This can't be undone."
        :action="route('admin.tasks.children.remove', [$taskId, 'dependencies', $dependency->id()])"
        :hx-target="'#dependency-' . $dependency->id()"
        confirmLabel="Remove"
    />
</li>
