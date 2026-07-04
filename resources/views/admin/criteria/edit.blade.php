@extends('admin.layouts.admin')
@section('title', 'Edit Criterion')
@section('content')
    @php $p = $criterion->toPrimitives(); @endphp
    <h1>Edit Criterion ({{ $p['complexity_level'] }})</h1>

    @if ($errors->any())
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    <form method="POST" action="{{ route('admin.criteria.update', $criterion->id()) }}">
        @csrf
        @method('PATCH')
        <p><strong>Dimension:</strong> {{ $p['parent_dimension_id'] }} ({{ $p['task_dimension_label'] }})</p>
        
        <label>Criterion Text <textarea name="criterion_text" required>{{ old('criterion_text', $p['criterion_text']) }}</textarea></label>
        <label>Weight (0-1) <input type="number" step="0.001" name="weight" value="{{ old('weight', $p['weight']) }}" required></label>
        <label>Dimension Weight (0-1) <input type="number" step="0.001" name="dimension_weight" value="{{ old('dimension_weight', $p['dimension_weight']) }}" required></label>
        
        {{-- The core descriptions and dimension mapping are usually read-only or updated separately, 
             but we allow text and weight updates per the command definition. --}}

        <button type="submit">Save Changes</button>
    </form>
@endsection
