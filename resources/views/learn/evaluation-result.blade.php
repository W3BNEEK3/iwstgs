@extends('layouts.shells.dashboard')

@section('title', 'Evaluation — ' . $result->taskTitle . ' — ' . config('app.name', 'Areyna'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">{{ $result->taskTitle }}</h1>
    <p style="color:var(--text-muted);margin:0;">Attempt {{ $result->attemptNumber }}@if ($result->commitSha) · commit <code>{{ substr($result->commitSha, 0, 7) }}</code>@endif</p>
</div>

@if ($result->isFromRepository && ! $result->isUncertain)
    {{-- Build milestones (design doc v2-01 §5): the learner sees the test outcome and the
         reviewer's notes, because they fix their own code with them. Classic challenges keep
         the "symptoms, not diagnosis" approach and show only the review status below. --}}
    <x-forms.form-section title="Review">
        <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;margin-bottom:var(--sp-md);">
            <x-ui.badge :tone="$result->passesThreshold ? 'success' : 'warning'">
                <x-ui.icon :name="$result->passesThreshold ? 'check_circle' : 'replay'" :size="14" />
                {{ $result->passesThreshold ? 'Milestone passed' : 'Not yet: push a fix and submit again' }}
            </x-ui.badge>
            @if ($result->ciStatus)
                <x-ui.badge :tone="$result->ciStatus === 'passed' ? 'success' : 'error'">Tests: {{ str_replace('_', ' ', $result->ciStatus) }}</x-ui.badge>
            @endif
        </div>
        @if ($result->ciSummary)
            <p style="margin:0 0 var(--sp-lg);font-size:var(--text-sm);">{{ $result->ciSummary }}</p>
        @endif

        <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
            @foreach ($result->dimensions as $dimension)
                @if ($dimension->evaluatorNotes || $dimension->criteriaMissed !== [])
                    <div class="panel">
                        <strong>{{ $dimension->taskDimensionLabel }}</strong>
                        @if ($dimension->evaluatorNotes)
                            <p style="margin:var(--sp-xs) 0 0;font-size:var(--text-sm);white-space:pre-line;">{{ $dimension->evaluatorNotes }}</p>
                        @endif
                        @if ($dimension->criteriaMissed !== [])
                            <ul style="margin:var(--sp-sm) 0 0;padding-left:var(--sp-lg);font-size:var(--text-sm);">
                                @foreach ($dimension->criteriaMissed as $missed)
                                    <li>{{ $missed }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </x-forms.form-section>

    <div style="margin-top:var(--sp-xl);display:flex;gap:var(--sp-sm);flex-wrap:wrap;">
        <x-ui.button :href="route('learn.milestone', [$result->learnerSessionId, $result->taskId])" severity="primary" icon="arrow_back">Back to the milestone</x-ui.button>
        <x-ui.button :href="route('learn.build', $result->projectId)" severity="secondary" icon="construction">Your build</x-ui.button>
    </div>
@else
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
    @if ($result->isFromRepository)
        <x-ui.button :href="route('learn.build', $result->projectId)" severity="secondary" icon="arrow_back">Back to your build</x-ui.button>
    @else
        <x-ui.button :href="route('learn.sprint-board', $result->projectId)" severity="secondary" icon="arrow_back">Back to Sprint Board</x-ui.button>
    @endif
</div>
@endif
@endsection
