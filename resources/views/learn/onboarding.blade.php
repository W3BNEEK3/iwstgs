@extends('layouts.shells.dashboard')

@section('title', 'Getting Started — ' . config('app.name', 'Areyna'))

@section('body')
<div style="margin-bottom:var(--sp-2xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Welcome to {{ $onboarding->projectTitle }}</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Read the brief below before your first sprint begins.</p>
</div>

<x-forms.form-section title="Business Context">
    @if ($onboarding->onboardingBriefing)
        <x-content.narrative-panel icon="mail" label="A message from the team">{{ $onboarding->onboardingBriefing }}</x-content.narrative-panel>
    @else
        <p style="margin:0;">{{ $onboarding->businessContext }}</p>
    @endif
</x-forms.form-section>

@if ($onboarding->scenarioTitle)
    <div style="margin-top:var(--sp-xl);"></div>
    <x-forms.form-section title="Your First Scenario: {{ $onboarding->scenarioTitle }}">
        @if ($onboarding->scenarioLearnerRoleLabel)
            <x-ui.badge tone="info" style="margin-bottom:var(--sp-md);">{{ $onboarding->scenarioLearnerRoleLabel }}</x-ui.badge>
        @endif
        @if ($onboarding->scenarioNarrativeContext)
            <p style="margin:0 0 var(--sp-md);">{{ $onboarding->scenarioNarrativeContext }}</p>
        @endif
        @if ($onboarding->scenarioSituationTrigger)
            @php
                $triggerIcon = match ($onboarding->scenarioSituationTriggerType) {
                    'slack_message'   => 'chat',
                    'email'           => 'mail',
                    'meeting_summary' => 'groups',
                    'incident_report' => 'report',
                    'ticket'          => 'confirmation_number',
                    'handover_note'   => 'assignment',
                    default           => 'chat',
                };
                $triggerLabel = str_replace('_', ' ', ucfirst($onboarding->scenarioSituationTriggerType ?? 'message'));
            @endphp
            <x-content.narrative-panel :icon="$triggerIcon" :label="$triggerLabel">
                {{ $onboarding->scenarioSituationTrigger }}
            </x-content.narrative-panel>
        @endif
    </x-forms.form-section>
@else
    <div style="margin-top:var(--sp-xl);"></div>
    <p style="color:var(--text-muted);font-size:var(--text-sm);">No scenario has been configured for this project yet.</p>
@endif

<form method="POST" action="{{ route('learn.onboarding.complete', $project) }}" style="margin-top:var(--sp-2xl);">
    @csrf
    <x-ui.button type="submit" severity="primary" icon="check">I'm ready to begin</x-ui.button>
</form>
@endsection
