@extends('admin.layouts.admin')

@section('title', 'AI Engine — Event Log')

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-md);margin-bottom:var(--sp-2xl);">
    <div>
        <h1 style="margin:0 0 var(--sp-xs);">AI Engine</h1>
        <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Tiroco decision audit log — every evaluation trigger and action taken.</p>
    </div>
    <div style="display:flex;gap:var(--sp-sm);">
        <a href="{{ route('admin.ai-engine.recommendations') }}" style="text-decoration:none;">
            <x-ui.button severity="secondary" icon="lightbulb">Tag Suggestions</x-ui.button>
        </a>
        <a href="{{ route('admin.ai-engine.config') }}" style="text-decoration:none;">
            <x-ui.button severity="secondary" icon="settings">Config</x-ui.button>
        </a>
    </div>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('admin.ai-engine.index') }}" style="display:flex;flex-wrap:wrap;gap:var(--sp-sm);margin-bottom:var(--sp-xl);align-items:flex-end;">
    <x-forms.select
        name="trigger_type"
        label="Trigger"
        :value="$filters['trigger_type'] ?? ''"
        placeholder="All triggers"
        :options="[
            'submission_received'       => 'Submission received',
            'failure_detected'          => 'Failure detected',
            'uncertain_evaluation'      => 'Uncertain evaluation',
            'mismatch_detected'         => 'Mismatch detected',
            'habit_detected'            => 'Habit detected',
            'dimension_weakness'        => 'Dimension weakness',
            'failure_threshold_exceeded'=> 'Failure threshold exceeded',
            'scenario_complete'         => 'Scenario complete',
            'knowledge_anchor_partial'  => 'Knowledge anchor partial',
        ]"
        style="min-width:200px;"
    />
    <x-forms.select
        name="action_taken"
        label="Action"
        :value="$filters['action_taken'] ?? ''"
        placeholder="All actions"
        :options="[
            'task_completed'                => 'Task completed',
            'consequence_injected'          => 'Consequence injected',
            'suggestion_queued'             => 'Suggestion queued',
            'follow_up_prompt_issued'       => 'Follow-up issued',
            'rank_review_triggered'         => 'Rank review triggered',
            'mismatch_flagged'              => 'Mismatch flagged',
            'human_review_queued'           => 'Human review queued',
            'dimension_targeted_next_scenario' => 'Dimension targeted',
            'knowledge_anchor_hint_issued'  => 'Anchor hint issued',
            'no_action'                     => 'No action',
        ]"
        style="min-width:200px;"
    />
    <x-forms.text-input name="date_from" type="date" label="From" :value="$filters['date_from'] ?? ''" />
    <x-forms.text-input name="date_to"   type="date" label="To"   :value="$filters['date_to'] ?? ''" />
    <div style="display:flex;gap:var(--sp-xs);padding-bottom:1px;">
        <x-ui.button type="submit" severity="primary" icon="filter_list">Filter</x-ui.button>
        <a href="{{ route('admin.ai-engine.index') }}" style="text-decoration:none;">
            <x-ui.button type="button" severity="secondary">Clear</x-ui.button>
        </a>
    </div>
</form>

@php $result = $events; $rows = $result['data']; $paginator = $result['paginator']; @endphp

@if (empty($rows))
    <div class="empty-state">
        <x-ui.icon name="smart_toy" :size="32" />
        <p>No AI events recorded yet. Events appear here once learners start submitting work with the <code>aimediation.claude_evaluation</code> flag enabled.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Learner</th>
                    <th>Trigger</th>
                    <th>Action</th>
                    <th>Det.</th>
                    <th>Conf.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $event)
                    <tr>
                        <td style="font-size:var(--text-xs);color:var(--text-muted);white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($event->createdAt)->format('d M H:i') }}
                        </td>
                        <td style="font-size:var(--text-sm);">
                            {{ $event->learnerName ?? substr($event->learnerId, 0, 8) . '…' }}
                        </td>
                        <td>
                            <x-ui.badge tone="{{ str_contains($event->triggerType, 'failure') || str_contains($event->triggerType, 'mismatch') ? 'danger' : (str_contains($event->triggerType, 'uncertain') ? 'warning' : 'neutral') }}">
                                {{ str_replace('_', ' ', $event->triggerType) }}
                            </x-ui.badge>
                        </td>
                        <td>
                            <x-ui.badge tone="{{ str_contains($event->actionTaken, 'completed') ? 'success' : (str_contains($event->actionTaken, 'human_review') || str_contains($event->actionTaken, 'injected') ? 'warning' : 'neutral') }}">
                                {{ str_replace('_', ' ', $event->actionTaken) }}
                            </x-ui.badge>
                        </td>
                        <td style="text-align:center;">
                            @if ($event->isDeterministic)
                                <span style="color:var(--success);" title="Deterministic">✓</span>
                            @else
                                <span style="color:var(--warning);" title="AI judgment">~</span>
                            @endif
                        </td>
                        <td style="font-size:var(--text-xs);color:var(--text-muted);">
                            {{ $event->confidenceScore !== null ? number_format($event->confidenceScore, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="mobile-row-cards">
        @foreach ($rows as $event)
            <div class="mobile-row-card">
                <div class="mobile-row-top">
                    <span>
                        <span class="mobile-row-title">{{ str_replace('_', ' ', ucfirst($event->triggerType)) }}</span>
                        <span class="mobile-row-sub">{{ $event->learnerName ?? substr($event->learnerId, 0, 8) }}
                            &mdash; {{ \Carbon\Carbon::parse($event->createdAt)->diffForHumans() }}</span>
                    </span>
                    <x-ui.badge tone="{{ str_contains($event->actionTaken, 'completed') ? 'success' : 'neutral' }}">
                        {{ str_replace('_', ' ', $event->actionTaken) }}
                    </x-ui.badge>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top:var(--sp-xl);">
        {{ $paginator->withQueryString()->links() }}
    </div>
@endif
@endsection
