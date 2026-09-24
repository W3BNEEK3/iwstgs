{{-- The one path to any destructive/irreversible action — never window.confirm()
     (design doc §10.3, do's/don'ts §17). Pair with a trigger:
     <x-ui.button severity="destructive" data-modal-open="delete-project">Delete</x-ui.button>
     <x-overlays.confirm-modal id="delete-project" title="Delete project?"
         description="This can't be undone." :action="route('admin.projects.destroy', $id)" />

     Pass hxTarget (+ optionally hxSwap) for an in-place HTMX removal instead of a full-page
     redirect — e.g. removing one row from a list of child items:
     <x-overlays.confirm-modal id="remove-x" title="Remove?" description="..."
         :action="route('admin.tasks.children.remove', [...])" :hx-target="'#row-'.$id" /> --}}
@props([
    'id',
    'title',
    'description',
    'action',
    'method' => 'DELETE',
    'confirmLabel' => 'Confirm',
    'confirmSeverity' => 'destructive',
    'cancelLabel' => 'Cancel',
    'hxTarget' => null,
    'hxSwap' => 'outerHTML',
])

<x-overlays.modal :id="$id" :title="$title">
    <p>{{ $description }}</p>
    <form
        method="POST"
        action="{{ $action }}"
        @if ($hxTarget)
            hx-{{ strtolower($method) }}="{{ $action }}"
            hx-target="{{ $hxTarget }}"
            hx-swap="{{ $hxSwap }}"
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
