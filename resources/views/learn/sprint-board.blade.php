@extends('layouts.shells.dashboard')

@section('title', 'Sprint ' . $board->sprintNumber . ' Board — ' . config('app.name', 'IWSTGS'))

@section('body')
@if (session('error'))
    <div class="badge badge-error" style="display:flex;width:fit-content;margin-bottom:var(--sp-md);">
        <span class="badge-dot" aria-hidden="true"></span>
        {{ session('error') }}
    </div>
@endif

<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Sprint {{ $board->sprintNumber }} Board</h1>
    @if ($board->scenarioTitle)
        <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-md);">{{ $board->scenarioTitle }}</p>
    @endif
    @if ($board->sprintGoal)
        <x-content.narrative-panel icon="flag" label="Sprint Goal">{{ $board->sprintGoal }}</x-content.narrative-panel>
    @endif
</div>

<div class="sprint-board-columns">
    @php
        // Column color paired with the existing title+count text, never color
        // alone (design doc §1 rule 4) — same badge-dot convention as badge.css.
        $columns = [
            ['title' => 'To Do', 'items' => $board->toDo, 'color' => 'var(--text-muted)'],
            ['title' => 'In Progress', 'items' => $board->inProgress, 'color' => 'var(--info-fg)'],
            ['title' => 'Done', 'items' => $board->done, 'color' => 'var(--success-fg)'],
            ['title' => 'Blocked', 'items' => $board->blocked, 'color' => 'var(--error-fg)'],
        ];
    @endphp

    @foreach ($columns as $column)
        <div>
            <h2 style="display:flex;align-items:center;gap:6px;font-size:var(--text-sm);text-transform:uppercase;letter-spacing:.04em;color:{{ $column['color'] }};margin-bottom:var(--sp-md);">
                <span class="badge-dot" style="width:8px;height:8px;"></span>
                {{ $column['title'] }} ({{ count($column['items']) }})
            </h2>
            <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
                @foreach ($column['items'] as $item)
                    @php
                        // Integration Spec §13.4 visual differentiation — color paired with an
                        // icon+label badge, never color alone (design doc §1 rule 4).
                        $injectedStyle = match ($item->visualTreatment ?? null) {
                            'consequence_amber'    => ['border' => 'var(--warning)',    'tone' => 'warning', 'icon' => 'priority_high', 'label' => 'Consequence'],
                            'suggestion_teal'      => ['border' => 'var(--suggestion)', 'tone' => 'info',    'icon' => 'lightbulb',     'label' => 'Suggestion'],
                            'diagnostic_deep_blue' => ['border' => 'var(--diagnostic)', 'tone' => 'info',    'icon' => 'diversity_2',   'label' => 'Diagnostic'],
                            default => null,
                        };
                    @endphp
                    <div class="mobile-row-card" style="cursor:default;{{ $injectedStyle ? 'border-color:' . $injectedStyle['border'] . ';border-width:2px;' : '' }}">
                        <div class="mobile-row-top">
                            <span class="mobile-row-title">{{ $item->title }}</span>
                        </div>
                        @if ($injectedStyle)
                            <x-ui.badge :tone="$injectedStyle['tone']">
                                <x-ui.icon :name="$injectedStyle['icon']" :size="14" /> {{ $injectedStyle['label'] }}
                            </x-ui.badge>
                        @endif
                        @if ($item->incidentTicketText)
                            <x-content.narrative-panel icon="confirmation_number" label="Incident Ticket">{{ $item->incidentTicketText }}</x-content.narrative-panel>
                        @endif
                        <x-ui.badge tone="neutral">{{ str_replace('_', ' ', ucfirst($item->priority)) }}</x-ui.badge>
                        @unless ($item->taskId)
                            <x-ui.badge tone="neutral">
                                <x-ui.icon name="checklist" :size="14" /> Self-tracked — not graded
                            </x-ui.badge>
                        @endunless

                        <div style="display:flex;flex-direction:column;gap:6px;margin-top:var(--sp-sm);">
                            @if ($item->status === 'in_sprint')
                                <form method="POST" action="{{ route('learn.sprint-board.update-status', [$project, $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="target_status" value="in_progress">
                                    <x-ui.button type="submit" severity="secondary" icon="play_arrow">Start</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('learn.sprint-board.update-status', [$project, $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="target_status" value="blocked">
                                    <x-ui.button type="submit" severity="secondary" icon="block">Block</x-ui.button>
                                </form>
                            @elseif ($item->status === 'in_progress')
                                @if ($item->taskId)
                                    @if ($item->submissionCount > 0)
                                        <x-ui.button :href="route('learn.task-submit', [$board->sessionId, $item->taskId])" severity="primary" icon="refresh">Resubmit (attempt {{ $item->submissionCount + 1 }})</x-ui.button>
                                    @else
                                        <x-ui.button :href="route('learn.task-submit', [$board->sessionId, $item->taskId])" severity="primary" icon="upload">Submit Work</x-ui.button>
                                    @endif
                                @endif
                                @unless ($item->taskId)
                                    <form method="POST" action="{{ route('learn.sprint-board.update-status', [$project, $item->id]) }}">
                                        @csrf
                                        <input type="hidden" name="target_status" value="done">
                                        <x-ui.button type="submit" severity="secondary" icon="check">Mark Complete</x-ui.button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('learn.sprint-board.update-status', [$project, $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="target_status" value="blocked">
                                    <x-ui.button type="submit" severity="secondary" icon="block">Block</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('learn.sprint-board.update-status', [$project, $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="target_status" value="in_sprint">
                                    <x-ui.button type="submit" severity="secondary" icon="arrow_back">Move Back</x-ui.button>
                                </form>
                            @elseif ($item->status === 'blocked')
                                <form method="POST" action="{{ route('learn.sprint-board.update-status', [$project, $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="target_status" value="in_progress">
                                    <x-ui.button type="submit" severity="secondary" icon="lock_open">Unblock</x-ui.button>
                                </form>
                                @unless ($item->taskId)
                                    <form method="POST" action="{{ route('learn.sprint-board.update-status', [$project, $item->id]) }}">
                                        @csrf
                                        <input type="hidden" name="target_status" value="done">
                                        <x-ui.button type="submit" severity="secondary" icon="check">Mark Complete</x-ui.button>
                                    </form>
                                @endunless
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<div style="margin-top:var(--sp-2xl);display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-xl);">
    <x-forms.form-section title="Reference Materials">
        @if (empty($board->referenceMaterials))
            <p style="color:var(--text-muted);font-size:var(--text-sm);">No reference materials for this scenario.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
                @foreach ($board->referenceMaterials as $material)
                    @php
                        $materialIcon = match ($material->type) {
                            'email'         => 'mail',
                            'slack_message' => 'chat',
                            'ticket'        => 'confirmation_number',
                            'report'        => 'summarize',
                            'notes'         => 'sticky_note_2',
                            default         => 'description',
                        };
                    @endphp
                    <x-content.narrative-panel :icon="$materialIcon" :label="str_replace('_', ' ', ucfirst($material->type))">
                        <details>
                            <summary style="cursor:pointer;font-weight:600;font-style:normal;">{{ $material->title }}</summary>
                            <p style="margin-top:var(--sp-sm);white-space:pre-line;">{{ $material->content }}</p>
                        </details>
                    </x-content.narrative-panel>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>

    <x-forms.form-section title="Artifact Vault">
        @if (empty($board->vaultItems))
            <p style="color:var(--text-muted);font-size:var(--text-sm);">No vault documents published for this project yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
                @foreach ($board->vaultItems as $vaultItem)
                    <div style="display:flex;align-items:center;gap:var(--sp-sm);">
                        <x-ui.icon name="description" :size="16" />
                        <span>{{ $vaultItem->title }}</span>
                        <x-ui.badge tone="neutral">{{ str_replace('_', ' ', $vaultItem->documentType) }}</x-ui.badge>
                    </div>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>
</div>

<form method="POST" action="{{ route('learn.sprint-board.submit', $project) }}" style="margin-top:var(--sp-2xl);">
    @csrf
    <input type="hidden" name="sprint_id" value="{{ $board->sprintId }}">
    <x-ui.button type="submit" severity="primary" icon="send">Submit Sprint</x-ui.button>
</form>
@endsection
