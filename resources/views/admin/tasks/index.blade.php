@extends('admin.layouts.admin')
@section('title', 'Tasks')
@section('content')
    <h1>Tasks — Scenario {{ $scenarioId }}</h1>
    <a href="{{ route('admin.scenarios.tasks.create', $scenarioId) }}">+ New Task</a>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Domain</th>
                <th>CAC</th>
                <th>Published</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tasks as $task)
                @include('admin.tasks._row', ['task' => $task, 'scenarioId' => $scenarioId])
            @empty
                <tr><td colspan="6">No tasks yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
