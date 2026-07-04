@extends('admin.layouts.admin')
@section('title', 'Edit Task')
@section('content')
    @php $p = $task->toPrimitives(); @endphp
    <h1>Edit Task: {{ $p['title'] }}</h1>

    @if ($errors->any())
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    <form method="POST" action="{{ route('admin.scenarios.tasks.update', [$scenarioId, $task->id()]) }}">
        @csrf
        @method('PATCH')
        <label>Title <input name="title" value="{{ old('title', $p['title']) }}" required></label>
        <label>Task brief <textarea name="task_brief" required>{{ old('task_brief', $p['task_brief']) }}</textarea></label>
        <button type="submit">Save Changes</button>
    </form>

    <hr>

    {{-- ===== Expected Deliverables ===== --}}
    <h2>Expected Deliverables</h2>
    <table id="deliverables-list">
        <thead><tr><th>Type</th><th>Label</th><th>Required</th><th></th></tr></thead>
        <tbody id="deliverables-body">
            @foreach ($task->expectedDeliverables() as $d)
                @include('admin.tasks._deliverable_row', ['deliverable' => $d, 'taskId' => $task->id()])
            @endforeach
        </tbody>
    </table>
    <form hx-post="{{ route('admin.tasks.deliverables.add', $task->id()) }}"
          hx-target="#deliverables-body" hx-swap="beforeend">
        @csrf
        <select name="type">
            <option value="written_explanation">written_explanation</option>
            <option value="artifact">artifact</option>
            <option value="code">code</option>
            <option value="diagram">diagram</option>
            <option value="document">document</option>
        </select>
        <input name="label" placeholder="Label" required>
        <label>Required <input type="checkbox" name="is_required" value="1" checked></label>
        <button type="submit">Add Deliverable</button>
    </form>

    <hr>

    {{-- ===== CAC Variants (max 3 — one per level) ===== --}}
    <h2>CAC Variants</h2>
    <table>
        <thead><tr><th>Level</th><th>Scenario Text</th><th></th></tr></thead>
        <tbody id="cac-variants-body">
            @foreach ($task->cacVariants() as $v)
                @include('admin.tasks._cac_variant_row', ['variant' => $v, 'taskId' => $task->id()])
            @endforeach
        </tbody>
    </table>
    @php $usedLevels = collect($task->cacVariants())->map(fn($v) => $v->toPrimitives()['complexity_level'])->all(); @endphp
    <form hx-post="{{ route('admin.tasks.cac-variants.add', $task->id()) }}"
          hx-target="#cac-variants-body" hx-swap="beforeend">
        @csrf
        <select name="complexity_level">
            @foreach (['low', 'mid', 'high'] as $level)
                <option value="{{ $level }}" @disabled(in_array($level, $usedLevels))>{{ $level }}</option>
            @endforeach
        </select>
        <textarea name="scenario_text" placeholder="Scenario text" required></textarea>
        <button type="submit">Add CAC Variant</button>
    </form>

    <hr>

    {{-- ===== Dependencies ===== --}}
    <h2>Dependencies (prerequisite tasks)</h2>
    <ul id="dependencies-body">
        @foreach ($task->dependencies() as $d)
            @include('admin.tasks._dependency_row', ['dependency' => $d, 'taskId' => $task->id()])
        @endforeach
    </ul>
    <form hx-post="{{ route('admin.tasks.dependencies.add', $task->id()) }}"
          hx-target="#dependencies-body" hx-swap="beforeend">
        @csrf
        <input name="prerequisite_task_id" placeholder="Prerequisite task UUID" required>
        <button type="submit">Add Dependency</button>
    </form>

    <hr>

    {{-- ===== Knowledge Anchors ===== --}}
    <h2>Knowledge Anchors</h2>
    <table>
        <thead><tr><th>Concept</th><th>Required</th><th>Remediation hint</th><th></th></tr></thead>
        <tbody id="anchors-body">
            @foreach ($task->knowledgeAnchors() as $a)
                @include('admin.tasks._anchor_row', ['anchor' => $a, 'taskId' => $task->id()])
            @endforeach
        </tbody>
    </table>
    <form hx-post="{{ route('admin.tasks.anchors.add', $task->id()) }}"
          hx-target="#anchors-body" hx-swap="beforeend">
        @csrf
        <input name="concept_name" placeholder="Concept name" required>
        <input name="domain" placeholder="Domain (optional)">
        <textarea name="application_expectation" placeholder="Application expectation (optional)"></textarea>
        <textarea name="remediation_hint" placeholder="Remediation hint (optional)"></textarea>
        <label>Required <input type="checkbox" name="is_required" value="1"></label>
        <button type="submit">Add Knowledge Anchor</button>
    </form>

    <hr>

    {{-- ===== Guidance Prompts (Decision 3) ===== --}}
    <h2>Guidance Prompts</h2>
    <table>
        <thead><tr><th>Dimension</th><th>Delivery</th><th>Autonomy filter</th><th>Text</th><th></th></tr></thead>
        <tbody id="prompts-body">
            @foreach ($task->guidancePrompts() as $p)
                @include('admin.tasks._prompt_row', ['prompt' => $p, 'taskId' => $task->id()])
            @endforeach
        </tbody>
    </table>
    <form hx-post="{{ route('admin.tasks.prompts.add', $task->id()) }}"
          hx-target="#prompts-body" hx-swap="beforeend">
        @csrf
        <input name="trigger_dimension" placeholder="e.g. dim_004 or test-coverage" required>
        <textarea name="prompt_text" placeholder="Guidance prompt text" required></textarea>
        <label>Delivery mode
            <select name="delivery_mode">
                <option value="proactive">proactive</option>
                <option value="reactive">reactive</option>
            </select>
        </label>
        <label>Autonomy filter
            <select name="autonomy_level_filter">
                <option value="">— any —</option>
                <option value="low">low</option>
                <option value="mid">mid</option>
                <option value="high">high</option>
            </select>
        </label>
        <button type="submit">Add Guidance Prompt</button>
    </form>
@endsection
