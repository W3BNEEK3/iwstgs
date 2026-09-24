@extends('admin.layouts.admin')

@section('title', 'New Scenario')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.projects.scenarios.index', $project->id()) }}">&larr; Back to scenarios</a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">New Scenario — {{ $project->title() }}</h1>

<form method="POST" action="{{ route('admin.projects.scenarios.store', $project->id()) }}">
    @csrf

    <x-forms.form-section title="Narrative">
        <x-forms.text-input name="title" label="Title" required autofocus />
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="narrative_context" label="Narrative Context" help="Second-person briefing — who the learner is, what just happened." required :rows="5" />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="Situation Trigger">
        <div class="field-grid">
            <x-forms.select name="situation_trigger_type" label="Trigger Type" required :options="[
                'slack_message'   => 'Slack Message',
                'email'           => 'Email',
                'meeting_summary' => 'Meeting Summary',
                'incident_report' => 'Incident Report',
                'ticket'          => 'Ticket',
                'handover_note'   => 'Handover Note',
            ]" />
            <x-forms.text-input name="learner_role_label" label="Learner Role Label" help="e.g. 'Lead Backend Developer' — narrative only, not a system role." />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="situation_trigger" label="Situation Trigger" help="The message or event that kicks off the scenario." required :rows="4" />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="Calibration">
        <div class="field-grid">
            <x-forms.select name="default_autonomy_level" label="Default Autonomy Level" required value="mid" :options="['low' => 'Low', 'mid' => 'Mid', 'high' => 'High']" />
            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                    <input type="checkbox" name="is_diagnostic" value="1">
                    Diagnostic scenario
                </label>
                <span class="field-help">CAC fixed at mid; used for initial rank assignment, not a regular project scenario.</span>
            </div>
        </div>
    </x-forms.form-section>

    <x-ui.button type="submit" severity="primary" icon="check">Create Scenario</x-ui.button>
</form>
@endsection
