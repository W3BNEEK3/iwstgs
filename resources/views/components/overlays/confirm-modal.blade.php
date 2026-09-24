{{-- The one path to any destructive/irreversible action — never window.confirm()
     (design doc §10.3, do's/don'ts §17). Pair with a trigger:
     <x-ui.button severity="destructive" data-modal-open="delete-project">Delete</x-ui.button>
     <x-overlays.confirm-modal id="delete-project" title="Delete project?"
         description="This can't be undone." :action="route('admin.projects.destroy', $id)" />

     Pass swapTarget (+ optionally swap) for an in-place removal via $ajax instead of a
     full-page redirect — e.g. removing one row from a list of child items:
     <x-overlays.confirm-modal id="remove-x" title="Remove?" description="..."
         :action="route('admin.tasks.children.remove', [...])" :swap-target="'#row-'.$id" /> --}}
@props([
    'id',
    'title',
    'description',
    'action',
    'method' => 'DELETE',
    'confirmLabel' => 'Confirm',
    'confirmSeverity' => 'destructive',
    'cancelLabel' => 'Cancel',
    'swapTarget' => null,
    'swap' => 'outerHTML',
])

<x-overlays.modal :id="$id" :title="$title">
    <p>{{ $description }}</p>
    <form
        method="POST"
        action="{{ $action }}"
        @if ($swapTarget)
            x-data
            @submit.prevent="$ajax($el.action, { method: '{{ $method }}', form: $el, target: '{{ $swapTarget }}', swap: '{{ $swap }}' }).then((ok) => ok && $el.closest('.modal-overlay')?.classList.remove('is-open'))"
        @endif
    >
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <div class="modal-actions">
            <x-ui.button type="button" severity="secondary" data-modal-close>{{ $cancelLabel }}</x-ui.button>
            <x-ui.button type="submit" :severity="$confirmSeverity">{{ $confirmLabel }}</x-ui.button>
        </div>
    </form>
</x-overlays.modal>
