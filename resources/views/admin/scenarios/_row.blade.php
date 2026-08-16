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
    <td><a href="{{ route('admin.projects.scenarios.edit', [$projectId, $scenario->id()]) }}">Edit</a></td>
</tr>
