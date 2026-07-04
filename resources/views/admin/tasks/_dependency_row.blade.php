<li id="dependency-{{ $dependency->id() }}">
    Prerequisite: <code>{{ $dependency->prerequisiteTaskId() }}</code>
    <button
        hx-delete="{{ route('admin.tasks.children.remove', [$taskId, 'dependencies', $dependency->id()]) }}"
        hx-target="#dependency-{{ $dependency->id() }}"
        hx-swap="outerHTML">
        Remove
    </button>
</li>
