@extends('admin.layouts.admin')
@section('title', 'Projects')
@section('content')
    <h1>Project Templates</h1>
    <a href="{{ route('admin.projects.create') }}">+ New Project</a>

    <table>
        <thead><tr><th>Title</th><th>Type</th><th>Difficulty</th><th>Published</th><th></th></tr></thead>
        <tbody>
            @forelse ($projects as $project)
                @include('admin.projects._row', ['project' => $project])
            @empty
                <tr><td colspan="5">No projects yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
