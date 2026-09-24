@php $oob = $oob ?? false; @endphp
<div class="mobile-row-card" id="scenario-card-{{ $scenario->id() }}" @if ($oob) data-swap-oob @endif style="cursor:default;">
    <div class="mobile-row-top">
        <span>
            <span class="mobile-row-title">{{ $scenario->sequenceOrder() }}. {{ $scenario->title() }}</span>
            <span class="mobile-row-sub">{{ str_replace('_', ' ', $scenario->situationTriggerType()->value) }}</span>
        </span>
    </div>
    <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-top:var(--sp-md);">
        <x-ui.button
            :severity="$scenario->isPublished() ? 'primary' : 'secondary'"
            x-data
            @click="$ajax('{{ route('admin.projects.scenarios.publish', [$projectId, $scenario->id()]) }}', { method: 'PATCH', target: '#scenario-row-{{ $scenario->id() }}' })"
        >
            {{ $scenario->isPublished() ? 'Published' : 'Draft' }}
        </x-ui.button>
        <x-ui.action-menu id="scenario-card-actions-{{ $scenario->id() }}">
            <a class="action-menu-item" href="{{ route('admin.scenarios.tasks.index', $scenario->id()) }}">
                <x-ui.icon name="task" :size="18" /> Tasks
            </a>
            <a class="action-menu-item" href="{{ route('admin.projects.scenarios.edit', [$projectId, $scenario->id()]) }}">
                <x-ui.icon name="edit" :size="18" /> Edit
            </a>
        </x-ui.action-menu>
    </div>
</div>
