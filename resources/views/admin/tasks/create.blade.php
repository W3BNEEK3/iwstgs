@extends('admin.layouts.admin')

@section('title', 'New Task')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.scenarios.tasks.index', $scenarioId) }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to tasks
    </a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">New Task</h1>

<form method="POST" action="{{ route('admin.scenarios.tasks.store', $scenarioId) }}">
    @csrf

    <x-forms.form-section title="Basics">
        <x-forms.text-input name="title" label="Title" :value="old('title')" required autofocus />
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="task_brief" label="Task Brief" :value="old('task_brief')" required :rows="4" />
        </div>
        <div class="field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.select name="task_type" label="Task Type" required :options="[
                'core' => 'Core', 'consequence' => 'Consequence', 'suggestion' => 'Suggestion',
                'diagnostic_scenario' => 'Diagnostic Scenario', 'diagnostic_consequence' => 'Diagnostic Consequence',
            ]" />
            <x-forms.text-input name="domain" label="Domain" help="Optional — e.g. backend" :value="old('domain')" />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="CAC Calibration">
        <label style="display:flex;align-items:center;gap:8px;">
            <input type="hidden" name="is_cac_runtime_set" value="0">
            <input type="checkbox" name="is_cac_runtime_set" value="1" checked
                   x-data @change="document.getElementById('fixed-cac-fields').classList.toggle('hidden', $el.checked)">
            CAC is runtime-set (varies per learner rather than fixed)
        </label>
        <div id="fixed-cac-fields" class="hidden field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.select name="fixed_complexity" label="Fixed Complexity" placeholder="—" :options="['low' => 'Low', 'mid' => 'Mid', 'high' => 'High']" />
            <x-forms.select name="fixed_autonomy" label="Fixed Autonomy" placeholder="—" :options="['low' => 'Low', 'mid' => 'Mid', 'high' => 'High']" />
            <x-forms.select name="fixed_context_fidelity" label="Fixed Context Fidelity" placeholder="—" :options="['low' => 'Low', 'mid' => 'Mid', 'high' => 'High']" />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="Flags & Details">
        <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="hidden" name="is_architectural" value="0">
                <input type="checkbox" name="is_architectural" value="1" @checked(old('is_architectural'))>
                Architectural
            </label>
            <label style="display:flex;align-items:center;gap:8px;">
                <input type="hidden" name="planning_layer_active" value="0">
                <input type="checkbox" name="planning_layer_active" value="1" @checked(old('planning_layer_active'))>
                Planning layer active
            </label>
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="model_response_summary" label="Model Response Summary" help="Optional — what a strong answer looks like" :value="old('model_response_summary')" :rows="3" />
        </div>
        <div class="field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="time_limit_minutes" type="number" min="1" label="Time Limit (minutes)" help="Optional" :value="old('time_limit_minutes')" />
        </div>
    </x-forms.form-section>

    <x-ui.button type="submit" severity="primary" icon="check">Create Task</x-ui.button>
</form>
@endsection
