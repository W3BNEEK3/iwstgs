<tr id="scenario-row-{{ $scenario->id() }}">
    <td>{{ $scenario->sequenceOrder() }}</td>
    <td>{{ $scenario->title() }}</td>
    <td><x-ui.badge tone="neutral">{{ str_replace('_', ' ', $scenario->situationTriggerType()->value) }}</x-ui.badge></td>
    <td>
        <x-ui.button
            :severity="$scenario->isPublished() ? 'primary' : 'secondary'"
            hx-patch="{{ route('admin.projects.scenarios.publish', [$projectId, $scenario->id()]) }}"
            hx-target="#scenario-row-{{ $scenario->id() }}"
            hx-swap="outerHTML"
        >
            {{ $scenario->isPublished() ? 'Published' : 'Draft' }}
        </x-ui.button>
    </td>
    <td>
        <x-ui.action-menu id="scenario-actions-{{ $scenario->id() }}">
            <a class="action-menu-item" href="{{ route('admin.scenarios.tasks.index', $scenario->id()) }}">
                <x-ui.icon name="task" :size="18" /> Tasks
            </a>
            <a class="action-menu-item" href="{{ route('admin.projects.scenarios.edit', [$projectId, $scenario->id()]) }}">
                <x-ui.icon name="edit" :size="18" /> Edit
            </a>
        </x-ui.action-menu>
    </td>
</tr>
@include('admin.scenarios._card', ['scenario' => $scenario, 'projectId' => $projectId, 'oob' => true])
