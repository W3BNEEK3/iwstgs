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
                    @include('admin.scenarios._row', ['scenario' => $scenario, 'projectId' => $project->id()])
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
