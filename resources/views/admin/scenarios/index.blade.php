@extends('admin.layouts.admin')

@section('title', 'Scenarios — ' . $project->title())

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.projects.index') }}">&larr; Back to projects</a>
</p>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <h1>Scenarios — {{ $project->title() }}</h1>
    <x-ui.button severity="primary" :href="route('admin.projects.scenarios.create', $project->id())" icon="add">New Scenario</x-ui.button>
</div>

@if (empty($scenarios))
    <div class="empty-state">
        <x-ui.icon name="auto_stories" :size="32" />
        <p>No scenarios yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>#</th><th>Title</th><th>Trigger</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($scenarios as $scenario)
                    <tr id="scenario-row-{{ $scenario->id() }}">
                        <td>{{ $scenario->sequenceOrder() }}</td>
                        <td>{{ $scenario->title() }}</td>
                        <td><x-ui.badge tone="neutral">{{ str_replace('_', ' ', $scenario->situationTriggerType()->value) }}</x-ui.badge></td>
                        <td>
                            <x-ui.button
                                :severity="$scenario->isPublished() ? 'primary' : 'secondary'"
                                hx-patch="{{ route('admin.projects.scenarios.publish', [$project->id(), $scenario->id()]) }}"
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
                                <a class="action-menu-item" href="{{ route('admin.projects.scenarios.edit', [$project->id(), $scenario->id()]) }}">
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
        @foreach ($scenarios as $scenario)
            @include('admin.scenarios._card', ['scenario' => $scenario, 'projectId' => $project->id()])
        @endforeach
    </div>
@endif
@endsection
