@extends('admin.layouts.admin')
@section('title', 'Add Criterion')
@section('content')
    <h1>Add Criterion to Task {{ $taskId }}</h1>

    @if ($errors->any())
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    <form method="POST" action="{{ route('admin.tasks.criteria.store', $taskId) }}">
        @csrf
        <label>Competency Dimension
            <select name="parent_dimension_id" required>
                <option value="">— Select Dimension —</option>
                @foreach ($dimensions as $dim)
                    <option value="{{ $dim->id }}">{{ $dim->shortLabel }} ({{ $dim->coreQuestion }})</option>
                @endforeach
            </select>
        </label>
        
        <label>Complexity Level
            <select name="complexity_level" required>
                <option value="low">low</option>
                <option value="mid">mid</option>
                <option value="high">high</option>
            </select>
        </label>
        
        <label>Task Dimension Label <input name="task_dimension_label" value="{{ old('task_dimension_label') }}" required></label>
        <label>Criterion Text <textarea name="criterion_text" required>{{ old('criterion_text') }}</textarea></label>
        <label>Weight (0-1) <input type="number" step="0.001" name="weight" value="{{ old('weight') }}" required></label>
        <label>Dimension Weight (0-1) <input type="number" step="0.001" name="dimension_weight" value="{{ old('dimension_weight') }}" required></label>
        <label>Claude Detection Hint <textarea name="claude_detection_hint" required>{{ old('claude_detection_hint') }}</textarea></label>
        
        <fieldset>
            <legend>Performance Level Descriptions</legend>
            <label>Distinguished <textarea name="distinguished_description" required>{{ old('distinguished_description') }}</textarea></label>
            <label>Proficient <textarea name="proficient_description" required>{{ old('proficient_description') }}</textarea></label>
            <label>Developing <textarea name="developing_description" required>{{ old('developing_description') }}</textarea></label>
            <label>Beginning <textarea name="beginning_description" required>{{ old('beginning_description') }}</textarea></label>
        </fieldset>

        <label>Architectural? <input type="checkbox" name="is_architectural" value="1" @checked(old('is_architectural'))></label>
        <label>Planning Layer? <input type="checkbox" name="is_planning_layer" value="1" @checked(old('is_planning_layer'))></label>
        <label>Reference Doc Anchor <input name="reference_doc_anchor" value="{{ old('reference_doc_anchor') }}"></label>

        <button type="submit">Create Criterion</button>
    </form>
@endsection
