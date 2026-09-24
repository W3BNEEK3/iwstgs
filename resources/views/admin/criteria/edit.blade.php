@extends('admin.layouts.admin')

@section('title', 'Edit Criterion')

@section('content')
@php $p = $criterion->toPrimitives(); @endphp

<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.tasks.criteria.index', $taskId) }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to criteria
    </a>
</p>

<h1 style="margin-bottom:var(--sp-xs);">Edit Criterion</h1>
<p style="color:var(--text-muted);margin:0 0 var(--sp-2xl);">
    <x-ui.badge tone="neutral">{{ ucfirst($p['complexity_level']) }}</x-ui.badge>
    {{ $p['task_dimension_label'] }} &middot; {{ $p['parent_dimension_id'] }}
</p>

<form method="POST" action="{{ route('admin.criteria.update', $criterion->id()) }}">
    @csrf
    @method('PATCH')

    <x-forms.form-section title="Criterion">
        <x-forms.textarea name="criterion_text" label="Criterion Text" :value="old('criterion_text', $p['criterion_text'])" required :rows="3" />
        <div class="field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="weight" type="number" step="0.001" min="0" max="1" label="Weight (0-1)" :value="old('weight', $p['weight'])" required />
            <x-forms.text-input name="dimension_weight" type="number" step="0.001" min="0" max="1" label="Dimension Weight (0-1)" :value="old('dimension_weight', $p['dimension_weight'])" required />
        </div>
    </x-forms.form-section>

    <x-ui.button type="submit" severity="primary" icon="check">Save Changes</x-ui.button>
</form>

<p style="margin-top:var(--sp-xl);color:var(--text-muted);font-size:var(--text-sm);">
    Dimension mapping and performance-level descriptions aren't editable from this form yet — only criterion text and weights are currently wired to <code>UpdateRubricCriterionCommand</code>.
</p>
@endsection
