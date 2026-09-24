@php $oob = $oob ?? false; @endphp
<div class="mobile-row-card" id="project-card-{{ $project->id() }}" @if ($oob) data-swap-oob @endif style="cursor:default;">
    <div class="mobile-row-top">
        <span>
            {{-- The card's primary field should be a short, unlabeled identifier (design doc
                 §13.2) — titles authored as "Short Name — Longer Description" show just the
                 short part here; the full title still appears in the desktop table and edit page. --}}
            <span class="mobile-row-title">{{ \Illuminate\Support\Str::before($project->toPrimitives()['title'], ' — ') }}</span>
            <span class="mobile-row-sub">{{ $project->toPrimitives()['project_type'] }} &middot; {{ ucfirst($project->toPrimitives()['difficulty_level']) }}</span>
        </span>
    </div>
    <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-top:var(--sp-md);">
        <x-ui.button
            :severity="$project->isPublished() ? 'primary' : 'secondary'"
            x-data
            @click="$ajax('{{ route('admin.projects.publish', $project->id()) }}', { method: 'PATCH', target: '#project-row-{{ $project->id() }}' })"
        >
            {{ $project->isPublished() ? 'Published' : 'Draft' }}
        </x-ui.button>
        <x-ui.action-menu id="project-card-actions-{{ $project->id() }}">
            <a class="action-menu-item" href="{{ route('admin.projects.scenarios.index', $project->id()) }}">
                <x-ui.icon name="auto_stories" :size="18" /> Scenarios
            </a>
            <a class="action-menu-item" href="{{ route('admin.projects.vault.index', $project->id()) }}">
                <x-ui.icon name="folder_open" :size="18" /> Vault
            </a>
            <a class="action-menu-item" href="{{ route('admin.projects.edit', $project->id()) }}">
                <x-ui.icon name="edit" :size="18" /> Edit
            </a>
        </x-ui.action-menu>
    </div>
</div>
