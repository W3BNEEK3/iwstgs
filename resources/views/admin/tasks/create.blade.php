@extends('admin.layouts.admin')
@section('title', 'New Task')
@section('content')
    <h1>New Task</h1>

    @if ($errors->any())
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    <form method="POST" action="{{ route('admin.scenarios.tasks.store', $scenarioId) }}">
        @csrf
        <label>Title <input name="title" value="{{ old('title') }}" required></label>
        <label>Task brief <textarea name="task_brief" required>{{ old('task_brief') }}</textarea></label>
        <label>Domain <input name="domain" value="{{ old('domain') }}"></label>
        <label>Task type
            <select name="task_type">
                <option value="core">core</option>
                <option value="consequence">consequence</option>
                <option value="suggestion">suggestion</option>
                <option value="diagnostic_scenario">diagnostic_scenario</option>
                <option value="diagnostic_consequence">diagnostic_consequence</option>
            </select>
        </label>
        <label>CAC is runtime-set?
            <input type="checkbox" name="is_cac_runtime_set" value="1" checked
                   _="on change toggle .hidden on #fixed-cac-fields">
        </label>
        <div id="fixed-cac-fields" class="hidden">
            <label>Fixed complexity
                <select name="fixed_complexity">
                    <option value="">—</option>
                    <option value="low">low</option>
                    <option value="mid">mid</option>
                    <option value="high">high</option>
                </select>
            </label>
            <label>Fixed autonomy
                <select name="fixed_autonomy">
                    <option value="">—</option>
                    <option value="low">low</option>
                    <option value="mid">mid</option>
                    <option value="high">high</option>
                </select>
            </label>
            <label>Fixed context fidelity
                <select name="fixed_context_fidelity">
                    <option value="">—</option>
                    <option value="low">low</option>
                    <option value="mid">mid</option>
                    <option value="high">high</option>
                </select>
            </label>
        </div>
        <label>Architectural? <input type="checkbox" name="is_architectural" value="1"></label>
        <label>Planning layer active? <input type="checkbox" name="planning_layer_active" value="1"></label>
        <label>Model response summary <textarea name="model_response_summary">{{ old('model_response_summary') }}</textarea></label>
        <label>Time limit (minutes) <input type="number" name="time_limit_minutes" value="{{ old('time_limit_minutes') }}" min="1"></label>
        <input type="hidden" name="is_cac_runtime_set" value="0">
        <button type="submit">Create Task</button>
    </form>
@endsection
