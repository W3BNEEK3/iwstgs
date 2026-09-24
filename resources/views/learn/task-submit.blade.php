@extends('layouts.shells.dashboard')

@section('title', 'Submit — ' . $form->taskTitle . ' — ' . config('app.name', 'Areyna'))

@php
    $hasWrittenExplanation = collect($form->deliverables)->contains(fn ($d) => $d->type === 'written_explanation');
    $hasCode = collect($form->deliverables)->contains(fn ($d) => $d->type === 'code');
    $fileDeliverables = collect($form->deliverables)->filter(fn ($d) => in_array($d->type, ['artifact', 'diagram', 'document'], true));
@endphp

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">{{ $form->taskTitle }}</h1>
    <p style="color:var(--text-muted);margin:0 0 var(--sp-sm);white-space:pre-line;">{{ $form->taskBrief }}</p>
    <div style="display:flex;gap:var(--sp-sm);align-items:center;flex-wrap:wrap;">
        <x-ui.badge tone="neutral">Attempt {{ $form->nextAttemptNumber }}</x-ui.badge>
        @if ($form->timeLimitMinutes)
            <x-ui.badge tone="neutral">{{ $form->timeLimitMinutes }} min suggested</x-ui.badge>
        @endif
        <x-ui.badge tone="info">Complexity: {{ ucfirst($form->cacComplexity) }}</x-ui.badge>
        <x-ui.badge tone="info">Guidance: {{ ucfirst($form->cacAutonomy) }}</x-ui.badge>
        <x-ui.badge tone="info">Realism: {{ ucfirst($form->cacContextFidelity) }}</x-ui.badge>
    </div>
</div>

@if ($form->resolvedScenarioText && $form->resolvedScenarioText !== $form->taskBrief)
    <x-content.narrative-panel icon="assignment" label="Briefing" style="margin-bottom:var(--sp-lg);">
        {{ $form->resolvedScenarioText }}
    </x-content.narrative-panel>
@endif

@if ($form->scaffoldingHint || $form->contextNote)
    <div style="display:flex;flex-direction:column;gap:var(--sp-xs);margin-bottom:var(--sp-lg);padding:var(--sp-md);border:1px solid var(--border);border-radius:var(--radius-md);background:var(--bg-subtle);">
        @if ($form->scaffoldingHint)
            <p style="margin:0;font-size:var(--text-sm);"><strong>Guidance:</strong> {{ $form->scaffoldingHint }}</p>
        @endif
        @if ($form->contextNote)
            <p style="margin:0;font-size:var(--text-sm);color:var(--text-muted);"><strong>Context:</strong> {{ $form->contextNote }}</p>
        @endif
    </div>
@endif

@if (!empty($form->referenceMaterials))
    <details class="form-section" style="margin-bottom:var(--sp-lg);" open>
        <summary style="cursor:pointer;font-weight:600;margin-bottom:var(--sp-md);">Reference materials</summary>
        <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
            @foreach ($form->referenceMaterials as $material)
                <x-reference-material :type="$material['type']" :title="$material['title']" :content="$material['content']" />
            @endforeach
        </div>
    </details>
@endif

@if (!empty($form->guidancePrompts))
    <x-forms.form-section title="Hints">
        <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
            @foreach ($form->guidancePrompts as $prompt)
                <div style="display:flex;align-items:flex-start;gap:var(--sp-sm);">
                    <x-ui.icon name="tips_and_updates" :size="18" />
                    <span style="font-size:var(--text-sm);">
                        {{ $prompt->promptText }}
                        <x-ui.badge tone="neutral">{{ $prompt->deliveryMode === 'proactive' ? 'Shown upfront' : 'On request' }}</x-ui.badge>
                    </span>
                </div>
            @endforeach
        </div>
    </x-forms.form-section>
@endif

@if ($errors->has('submission'))
    <div class="field has-error" style="margin-bottom:var(--sp-lg);">
        <span class="field-error">
            <x-ui.icon name="error" :size="12" />
            {{ $errors->first('submission') }}
        </span>
    </div>
@endif

<form method="POST" action="{{ route('learn.task-submit.store', [$sessionId, $taskId]) }}" enctype="multipart/form-data">
    @csrf

    <x-forms.form-section title="Expected Deliverables">
        <div style="display:flex;flex-direction:column;gap:var(--sp-sm);margin-bottom:var(--sp-lg);">
            @foreach ($form->deliverables as $deliverable)
                <div style="display:flex;align-items:flex-start;gap:var(--sp-sm);">
                    <x-ui.icon name="{{ $deliverable->isRequired ? 'check_box' : 'check_box_outline_blank' }}" :size="18" />
                    <span>
                        <strong>{{ $deliverable->label }}</strong>
                        @if ($deliverable->isRequired)
                            <x-ui.badge tone="warning">Required</x-ui.badge>
                        @endif
                        @if ($deliverable->description)
                            <span style="display:block;color:var(--text-muted);font-size:var(--text-sm);">{{ $deliverable->description }}</span>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    </x-forms.form-section>

    @if ($hasWrittenExplanation)
        <x-forms.form-section title="Written Explanation">
            <x-forms.textarea name="layer1_text" :rows="6" />
        </x-forms.form-section>
    @endif

    @if ($hasCode)
        <x-forms.form-section title="Code">
            <x-forms.textarea name="layer3_code" :rows="14" style="font-family:var(--font-mono, monospace);" />
        </x-forms.form-section>
    @endif

    @if ($fileDeliverables->isNotEmpty())
        <x-forms.form-section title="Artifacts">
            @foreach ($fileDeliverables as $deliverable)
                @php $fieldName = "artifacts.{$deliverable->id}"; @endphp
                <div class="field @if ($errors->has("{$fieldName}.*")) has-error @endif" style="margin-bottom:var(--sp-md);">
                    <label for="artifact-{{ $deliverable->id }}">
                        {{ $deliverable->label }}
                        @if ($deliverable->isRequired) <span aria-hidden="true">*</span> @endif
                        <x-ui.badge tone="neutral">{{ ucfirst($deliverable->type) }}</x-ui.badge>
                    </label>
                    <input type="file" id="artifact-{{ $deliverable->id }}" name="artifacts[{{ $deliverable->id }}][]" multiple>
                    @if ($errors->has("{$fieldName}.*"))
                        <span class="field-error">
                            <x-ui.icon name="error" :size="12" />
                            {{ $errors->first("{$fieldName}.*") }}
                        </span>
                    @else
                        <span class="field-help">PDF, Word, image, text, or Markdown files, up to 10MB each.</span>
                    @endif
                </div>
            @endforeach
        </x-forms.form-section>
    @endif

    <x-ui.button type="submit" severity="primary" icon="send" style="margin-top:var(--sp-lg);">Submit</x-ui.button>
</form>
@endsection
