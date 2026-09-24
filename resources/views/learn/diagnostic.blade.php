@extends('layouts.shells.dashboard')

@section('title', 'Initial Skills Diagnostic — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">{{ $board->scenarioTitle }}</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-md);">
        A short, one-time assessment that sets your starting rank. There's no sprint board here — just work through each task below.
    </p>
    <p style="margin:0 0 var(--sp-md);">{{ $board->narrativeContext }}</p>
    <x-content.narrative-panel icon="mail" label="Briefing">{{ $board->situationTrigger }}</x-content.narrative-panel>
</div>

@if (!empty($board->referenceMaterials))
    <x-forms.form-section title="Reference Materials">
        <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
            @foreach ($board->referenceMaterials as $material)
                <x-reference-material :type="$material->type" :title="$material->title" :content="$material->content" />
            @endforeach
        </div>
    </x-forms.form-section>
@endif

<x-forms.form-section title="Tasks">
    <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
        @foreach ($board->tasks as $task)
            <div class="mobile-row-card" style="cursor:default;">
                <div class="mobile-row-top">
                    <span class="mobile-row-title">{{ $task->title }}</span>
                </div>
                <p style="margin:var(--sp-xs) 0 var(--sp-sm);color:var(--text-muted);font-size:var(--text-sm);white-space:pre-line;">{{ $task->taskBrief }}</p>

                @if ($task->submissionCount > 0)
                    <x-ui.badge tone="success">
                        <x-ui.icon name="check" :size="14" /> Submitted
                    </x-ui.badge>
                    <div style="margin-top:var(--sp-sm);">
                        <x-ui.button :href="route('learn.task-submit', [$board->sessionId, $task->taskId])" severity="secondary" icon="refresh">Resubmit (attempt {{ $task->submissionCount + 1 }})</x-ui.button>
                    </div>
                @else
                    <x-ui.button :href="route('learn.task-submit', [$board->sessionId, $task->taskId])" severity="primary" icon="upload">Submit Work</x-ui.button>
                @endif
            </div>
        @endforeach
    </div>
</x-forms.form-section>

<p style="color:var(--text-muted);font-size:var(--text-sm);margin-top:var(--sp-xl);">
    Your starting rank is assigned automatically once both tasks have been submitted and checked.
</p>
@endsection
