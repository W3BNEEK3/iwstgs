<tr id="project-row-{{ $project->id() }}">
    <td>{{ $project->toPrimitives()['title'] }}</td>
    <td>{{ $project->toPrimitives()['project_type'] }}</td>
    <td>{{ $project->toPrimitives()['difficulty_level'] }}</td>
    <td>
        <button
            hx-patch="{{ route('admin.projects.publish', $project->id()) }}"
            hx-target="#project-row-{{ $project->id() }}"
            hx-swap="outerHTML">
            {{ $project->isPublished() ? 'Published ✓' : 'Draft' }}
        </button>
    </td>
    <td><a href="{{ route('admin.projects.edit', $project->id()) }}">Edit</a></td>
</tr>
