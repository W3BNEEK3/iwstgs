@php $oob = $oob ?? false; @endphp
<div class="mobile-row-card" id="task-card-{{ $task->id() }}" @if ($oob) data-swap-oob @endif style="cursor:default;">
    <div class="mobile-row-top">
        <span>
            <span class="mobile-row-title">{{ $task->toPrimitives()['title'] }}</span>
            <span class="mobile-row-sub">{{ $task->toPrimitives()['task_type'] }} &middot; {{ $task->toPrimitives()['domain'] ?? '—' }}</span>
        </span>
    </div>
    <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-top:var(--sp-md);">
        <x-ui.button
            :severity="$task->isPublished() ? 'primary' : 'secondary'"
            x-data
            @click="$ajax('{{ route('admin.scenarios.tasks.publish', [$scenarioId, $task->id()]) }}', { method: 'PATCH', target: '#task-row-{{ $task->id() }}' })"
        >
            {{ $task->isPublished() ? 'Published' : 'Draft' }}
        </x-ui.button>
        <x-ui.action-menu id="task-card-actions-{{ $task->id() }}">
            <a class="action-menu-item" href="{{ route('admin.tasks.criteria.index', $task->id()) }}">
                <x-ui.icon name="checklist" :size="18" /> Criteria
            </a>
            <a class="action-menu-item" href="{{ route('admin.scenarios.tasks.edit', [$scenarioId, $task->id()]) }}">
                <x-ui.icon name="edit" :size="18" /> Edit
            </a>
        </x-ui.action-menu>
    </div>
</div>
