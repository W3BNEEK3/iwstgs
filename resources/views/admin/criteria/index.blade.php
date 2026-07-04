@extends('admin.layouts.admin')
@section('title', 'Rubric Criteria')
@section('content')
    <h1>Rubric Criteria for Task {{ $taskId }}</h1>
    <a href="{{ route('admin.tasks.criteria.create', $taskId) }}">+ Add Criterion</a>

    <table>
        <thead>
            <tr>
                <th>Complexity</th>
                <th>Dimension (Label)</th>
                <th>Weight</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($criteria as $criterion)
                @include('admin.criteria._row', ['criterion' => $criterion])
            @empty
                <tr><td colspan="4">No criteria authored yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
