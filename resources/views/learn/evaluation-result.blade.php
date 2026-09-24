@extends('layouts.shells.dashboard')

@section('title', 'Evaluation — ' . $result->taskTitle . ' — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">{{ $result->taskTitle }}</h1>
    <p style="color:var(--text-muted);margin:0;">Attempt {{ $result->attemptNumber }}</p>
</div>

<x-forms.form-section title="Under Review">
    <div style="display:flex;align-items:center;gap:var(--sp-md);margin-bottom:var(--sp-md);">
        <x-ui.badge tone="warning"><x-ui.icon name="hourglass_top" :size="14" /> Under human review</x-ui.badge>
    </div>

    <div style="padding:var(--sp-md);background:var(--bg-subtle);border-radius:var(--radius-md);border:1px solid var(--border);">
        <p style="margin:0 0 var(--sp-xs);font-weight:600;font-size:var(--text-sm);">Your submission couldn't be confidently evaluated and has been queued for human review.</p>
        @if ($result->followUpPromptText)
            <p style="margin:0;font-size:var(--text-sm);">Follow-up question: {{ $result->followUpPromptText }}</p>
        @endif
    </div>
</x-forms.form-section>

<div style="margin-top:var(--sp-xl);">
    <x-ui.button :href="route('learn.sprint-board', $result->projectId)" severity="secondary" icon="arrow_back">Back to Sprint Board</x-ui.button>
</div>
@endsection
