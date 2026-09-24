<tr id="project-row-{{ $project->id() }}">
    <td>{{ $project->toPrimitives()['title'] }}</td>
    <td>{{ $project->toPrimitives()['project_type'] }}</td>
    <td>{{ ucfirst($project->toPrimitives()['difficulty_level']) }}</td>
    <td>
        <x-ui.button
            :severity="$project->isPublished() ? 'primary' : 'secondary'"
            x-data
            @click="$ajax('{{ route('admin.projects.publish', $project->id()) }}', { method: 'PATCH', target: '#project-row-{{ $project->id() }}' })"
        >
            {{ $project->isPublished() ? 'Published' : 'Draft' }}
        </x-ui.button>
    </td>
    <td>
        <x-ui.action-menu id="project-actions-{{ $project->id() }}">
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
    </td>
</tr>
@include('admin.projects._card', ['project' => $project, 'oob' => true])
