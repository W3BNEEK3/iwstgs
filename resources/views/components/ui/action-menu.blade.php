{{-- <x-ui.action-menu id="project-actions-{{ $id }}">
       <a class="action-menu-item" href="...">Scenarios</a>
       <a class="action-menu-item" href="...">Vault</a>
     </x-ui.action-menu>
     Row actions beyond one or two go in a dropdown, not bare inline text links —
     design doc §9/§16 (dropdown menu is a real overlay), reuses the exact
     [data-dropdown-toggle]/[data-dropdown] behavior already wired for the
     profile menu. --}}
@props(['id'])

<div class="action-menu-anchor">
    <button type="button" class="btn-icon" data-dropdown-toggle="{{ $id }}" aria-haspopup="true" aria-expanded="false" aria-label="Actions">
        <x-ui.icon name="more_vert" :size="20" />
    </button>
    <div id="{{ $id }}" class="action-menu" data-dropdown hidden>
        {{ $slot }}
    </div>
</div>
