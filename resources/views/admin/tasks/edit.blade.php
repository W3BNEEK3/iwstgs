@extends('admin.layouts.admin')

@section('title', 'Edit Task')

@section('content')
@php $p = $task->toPrimitives(); @endphp

<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.scenarios.tasks.index', $scenarioId) }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to tasks
    </a>
</p>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <h1>{{ $p['title'] }}</h1>
    <x-ui.button severity="secondary" :href="route('admin.tasks.criteria.index', $task->id())" icon="checklist">Rubric Criteria</x-ui.button>
</div>

<form method="POST" action="{{ route('admin.scenarios.tasks.update', [$scenarioId, $task->id()]) }}">
    @csrf
    @method('PATCH')

    <x-forms.form-section title="Basics">
        <x-forms.text-input name="title" label="Title" :value="old('title', $p['title'])" required />
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="task_brief" label="Task Brief" :value="old('task_brief', $p['task_brief'])" required :rows="4" />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="Adaptive Links">
        <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-lg);">
            One task UUID per line (or comma-separated). The Adaptive Engine picks from these when a
            learner fails this task (consequence) or repeatedly struggles with this task's dimension
            elsewhere (suggestion).
        </p>
        <div class="field-grid">
            <x-forms.textarea name="consequence_task_ids" label="Consequence Task IDs" :value="old('consequence_task_ids', implode(\"\n\", $p['consequence_task_ids']))" :rows="4" />
            <x-forms.textarea name="suggestion_task_ids" label="Suggestion Task IDs" :value="old('suggestion_task_ids', implode(\"\n\", $p['suggestion_task_ids']))" :rows="4" />
        </div>
    </x-forms.form-section>

    <x-ui.button type="submit" severity="primary" icon="check">Save Changes</x-ui.button>
</form>

<p style="margin:var(--sp-xl) 0 var(--sp-2xl);color:var(--text-muted);font-size:var(--text-sm);">
    Type, domain, CAC calibration, and other flags aren't editable after creation yet — only title, brief, and adaptive links are currently wired to <code>UpdateTaskCommand</code>.
</p>

{{-- ===== Expected Deliverables ===== --}}
<x-forms.form-section title="Expected Deliverables">
    <div class="table-frame" style="margin-bottom:var(--sp-lg);">
        <table class="data-table">
            <thead><tr><th>Type</th><th>Label</th><th>Required</th><th></th></tr></thead>
            <tbody id="deliverables-body">
                @foreach ($task->expectedDeliverables() as $d)
                    @include('admin.tasks._deliverable_row', ['deliverable' => $d, 'taskId' => $task->id()])
                @endforeach
            </tbody>
        </table>
    </div>
    <form hx-post="{{ route('admin.tasks.deliverables.add', $task->id()) }}" hx-target="#deliverables-body" hx-swap="beforeend" hx-on::after-request="this.reset()">
        @csrf
        <div class="field-grid">
            <x-forms.select name="type" label="Type" :options="[
                'written_explanation' => 'Written Explanation', 'artifact' => 'Artifact', 'code' => 'Code',
                'diagram' => 'Diagram', 'document' => 'Document',
            ]" />
            <x-forms.text-input name="label" label="Label" required />
        </div>
        <label style="display:flex;align-items:center;gap:8px;margin-top:var(--sp-md);">
            <input type="checkbox" name="is_required" value="1" checked>
            Required
        </label>
        <x-ui.button type="submit" severity="secondary" icon="add" style="margin-top:var(--sp-md);">Add Deliverable</x-ui.button>
    </form>
</x-forms.form-section>

{{-- ===== CAC Variants ===== --}}
<x-forms.form-section title="CAC Variants">
    <div class="table-frame" style="margin-bottom:var(--sp-lg);">
        <table class="data-table">
            <thead><tr><th>Level</th><th>Scenario Text</th><th></th></tr></thead>
            <tbody id="cac-variants-body">
                @foreach ($task->cacVariants() as $v)
                    @include('admin.tasks._cac_variant_row', ['variant' => $v, 'taskId' => $task->id()])
                @endforeach
            </tbody>
        </table>
    </div>
    @php $usedLevels = collect($task->cacVariants())->map(fn ($v) => $v->toPrimitives()['complexity_level'])->all(); @endphp
    <form hx-post="{{ route('admin.tasks.cac-variants.add', $task->id()) }}" hx-target="#cac-variants-body" hx-swap="beforeend" hx-on::after-request="this.reset()">
        @csrf
        <div class="field-grid">
            <x-forms.select name="complexity_level" label="Level" :options="collect(['low' => 'Low', 'mid' => 'Mid', 'high' => 'High'])->filter(fn ($l, $k) => ! in_array($k, $usedLevels, true))->all()" />
        </div>
        <div style="margin-top:var(--sp-md);">
            <x-forms.textarea name="scenario_text" label="Scenario Text" required :rows="3" />
        </div>
        <x-ui.button type="submit" severity="secondary" icon="add" style="margin-top:var(--sp-md);">Add CAC Variant</x-ui.button>
    </form>
</x-forms.form-section>

{{-- ===== Dependencies ===== --}}
<x-forms.form-section title="Dependencies (prerequisite tasks)">
    <ul id="dependencies-body" style="list-style:none;padding:0;margin:0 0 var(--sp-lg);display:flex;flex-direction:column;gap:var(--sp-sm);">
        @foreach ($task->dependencies() as $d)
            @include('admin.tasks._dependency_row', ['dependency' => $d, 'taskId' => $task->id()])
        @endforeach
    </ul>
    <form hx-post="{{ route('admin.tasks.dependencies.add', $task->id()) }}" hx-target="#dependencies-body" hx-swap="beforeend" hx-on::after-request="this.reset()">
        @csrf
        <x-forms.text-input name="prerequisite_task_id" label="Prerequisite Task UUID" required />
        <x-ui.button type="submit" severity="secondary" icon="add" style="margin-top:var(--sp-md);">Add Dependency</x-ui.button>
    </form>
</x-forms.form-section>

{{-- ===== Knowledge Anchors ===== --}}
<x-forms.form-section title="Knowledge Anchors">
    <div class="table-frame" style="margin-bottom:var(--sp-lg);">
        <table class="data-table">
            <thead><tr><th>Concept</th><th>Required</th><th>Remediation Hint</th><th></th></tr></thead>
            <tbody id="anchors-body">
                @foreach ($task->knowledgeAnchors() as $a)
                    @include('admin.tasks._anchor_row', ['anchor' => $a, 'taskId' => $task->id()])
                @endforeach
            </tbody>
        </table>
    </div>
    <form hx-post="{{ route('admin.tasks.anchors.add', $task->id()) }}" hx-target="#anchors-body" hx-swap="beforeend" hx-on::after-request="this.reset()">
        @csrf
        <div class="field-grid">
            <x-forms.text-input name="concept_name" label="Concept Name" required />
            <x-forms.text-input name="domain" label="Domain" help="Optional" />
        </div>
        <div style="margin-top:var(--sp-md);">
            <x-forms.textarea name="application_expectation" label="Application Expectation" help="Optional" :rows="2" />
        </div>
        <div style="margin-top:var(--sp-md);">
            <x-forms.textarea name="remediation_hint" label="Remediation Hint" help="Optional" :rows="2" />
        </div>
        <label style="display:flex;align-items:center;gap:8px;margin-top:var(--sp-md);">
            <input type="checkbox" name="is_required" value="1">
            Required
        </label>
        <x-ui.button type="submit" severity="secondary" icon="add" style="margin-top:var(--sp-md);">Add Knowledge Anchor</x-ui.button>
    </form>
</x-forms.form-section>

{{-- ===== Guidance Prompts ===== --}}
<x-forms.form-section title="Guidance Prompts">
    <div class="table-frame" style="margin-bottom:var(--sp-lg);">
        <table class="data-table">
            <thead><tr><th>Dimension</th><th>Delivery</th><th>Autonomy Filter</th><th>Text</th><th></th></tr></thead>
            <tbody id="prompts-body">
                @foreach ($task->guidancePrompts() as $prompt)
                    @include('admin.tasks._prompt_row', ['prompt' => $prompt, 'taskId' => $task->id()])
                @endforeach
            </tbody>
        </table>
    </div>
    <form hx-post="{{ route('admin.tasks.prompts.add', $task->id()) }}" hx-target="#prompts-body" hx-swap="beforeend" hx-on::after-request="this.reset()">
        @csrf
        <x-forms.text-input name="trigger_dimension" label="Trigger Dimension" help="e.g. dim_004 or test-coverage" required />
        <div style="margin-top:var(--sp-md);">
            <x-forms.textarea name="prompt_text" label="Prompt Text" required :rows="2" />
        </div>
        <div class="field-grid" style="margin-top:var(--sp-md);">
            <x-forms.select name="delivery_mode" label="Delivery Mode" :options="['proactive' => 'Proactive', 'reactive' => 'Reactive']" />
            <x-forms.select name="autonomy_level_filter" label="Autonomy Filter" placeholder="— any —" :options="['low' => 'Low', 'mid' => 'Mid', 'high' => 'High']" />
        </div>
        <x-ui.button type="submit" severity="secondary" icon="add" style="margin-top:var(--sp-md);">Add Guidance Prompt</x-ui.button>
    </form>
</x-forms.form-section>
@endsection
