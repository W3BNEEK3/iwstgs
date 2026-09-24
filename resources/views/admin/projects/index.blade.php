@extends('admin.layouts.admin')

@section('title', 'Projects')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <h1>Project Templates</h1>
    <x-ui.button severity="primary" :href="route('admin.projects.create')" icon="add">New Project</x-ui.button>
</div>

@if (empty($projects))
    <div class="empty-state">
        <x-ui.icon name="inventory_2" :size="32" />
        <p>No projects yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Type</th><th>Difficulty</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr id="project-row-{{ $project->id() }}">
                        <td>{{ $project->toPrimitives()['title'] }}</td>
                        <td>{{ $project->toPrimitives()['project_type'] }}</td>
                        <td>{{ ucfirst($project->toPrimitives()['difficulty_level']) }}</td>
                        <td>
                            <x-ui.button
                                :severity="$project->isPublished() ? 'primary' : 'secondary'"
                                hx-patch="{{ route('admin.projects.publish', $project->id()) }}"
                                hx-target="#project-row-{{ $project->id() }}"
                                hx-swap="outerHTML"
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
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-row-cards">
        @foreach ($projects as $project)
            @include('admin.projects._card', ['project' => $project])
        @endforeach
    </div>
@endif
@endsection
