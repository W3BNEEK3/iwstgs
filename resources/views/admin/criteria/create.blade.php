@extends('admin.layouts.admin')

@section('title', 'Add Criterion')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.tasks.criteria.index', $taskId) }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to criteria
    </a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">Add Criterion</h1>

@if ($errors->has('rubric_set'))
    <div class="field has-error" style="margin-bottom:var(--sp-lg);">
        <span class="field-error"><x-ui.icon name="error" :size="12" /> {{ $errors->first('rubric_set') }}</span>
    </div>
@endif

<form method="POST" action="{{ route('admin.tasks.criteria.store', $taskId) }}">
    @csrf

    <x-forms.form-section title="Dimension & Complexity">
        <div class="field-grid">
            <x-forms.select name="parent_dimension_id" label="Competency Dimension" required placeholder="Select..." :options="collect($dimensions)->mapWithKeys(fn ($d) => [$d->id => $d->shortLabel])->all()" />
            <x-forms.select name="complexity_level" label="Complexity Level" required :options="['low' => 'Low', 'mid' => 'Mid', 'high' => 'High']" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="task_dimension_label" label="Task Dimension Label" :value="old('task_dimension_label')" required />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="criterion_text" label="Criterion Text" :value="old('criterion_text')" required :rows="3" />
        </div>
        <div class="field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="weight" type="number" step="0.001" min="0" max="1" label="Weight (0-1)" :value="old('weight')" required />
            <x-forms.text-input name="dimension_weight" type="number" step="0.001" min="0" max="1" label="Dimension Weight (0-1)" :value="old('dimension_weight')" required />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="claude_detection_hint" label="Claude Detection Hint" help="What should the evaluator look for?" :value="old('claude_detection_hint')" required :rows="3" />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="Performance Level Descriptions">
        <x-forms.textarea name="distinguished_description" label="Distinguished" :value="old('distinguished_description')" required :rows="2" />
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="proficient_description" label="Proficient" :value="old('proficient_description')" required :rows="2" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="developing_description" label="Developing" :value="old('developing_description')" required :rows="2" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="beginning_description" label="Beginning" :value="old('beginning_description')" required :rows="2" />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="Flags">
        <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="is_architectural" value="1" @checked(old('is_architectural'))>
                Architectural
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="is_planning_layer" value="1" @checked(old('is_planning_layer'))>
                Planning layer
            </label>
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="reference_doc_anchor" label="Reference Doc Anchor" help="Optional — e.g. 'MedQueue PRD §2'" :value="old('reference_doc_anchor')" />
        </div>
    </x-forms.form-section>

    <x-ui.button type="submit" severity="primary" icon="check">Create Criterion</x-ui.button>
</form>
@endsection
