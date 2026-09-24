@extends('layouts.shells.dashboard')

@php
    $guideContext = ['newScenario' => $planning->scenarioSequence > 1];
@endphp

@section('title', 'Sprint ' . $planning->sprintNumber . ' Planning — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="margin-bottom:var(--sp-2xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Sprint {{ $planning->sprintNumber }} Planning</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Choose what you'll work on this sprint, then write your sprint goal and confirm.</p>
</div>

@php
    $goalLabel = match ($planning->sprintGoalSource) {
        'system_defined'             => 'Sprint Goal — Set by Your Team Lead',
        'learner_completed_template' => 'Sprint Goal Template — Complete the Blanks',
        default                      => 'Your Sprint Goal',
    };
@endphp
<x-forms.form-section title="Sprint Goal">
    <form method="POST" action="{{ route('learn.sprint-planning.goal', $project) }}">
        @csrf
        <input type="hidden" name="sprint_id" value="{{ $planning->sprintId }}">
        <x-content.narrative-panel icon="flag" :label="$goalLabel">
            <x-forms.textarea
                name="goal"
                :value="$planning->sprintGoal"
                :rows="4"
                required
                :readonly="$planning->sprintGoalSource === 'system_defined' && $planning->sprintGoal"
                :help="$planning->showsQualityHint ? 'Aim for a goal that is specific, aligned with the project brief, and correctly scoped to what this sprint can realistically deliver.' : null"
            />
        </x-content.narrative-panel>
        <x-ui.button type="submit" severity="secondary" icon="save" style="margin-top:var(--sp-md);">Save Goal</x-ui.button>
    </form>
</x-forms.form-section>

<div style="margin-top:var(--sp-2xl);display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-xl);">
    <x-forms.form-section title="Backlog ({{ count($planning->backlogItems) }})">
        @if (empty($planning->backlogItems))
            <div class="empty-state">
                <x-ui.icon name="inventory_2" />
                <p>Nothing left in the backlog.</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
                @foreach ($planning->backlogItems as $item)
                    <div class="mobile-row-card" style="cursor:default;">
                        <div class="mobile-row-top">
                            <span>
                                <span class="mobile-row-title">{{ $item->title }}</span>
                                <x-ui.badge tone="neutral">{{ str_replace('_', ' ', ucfirst($item->priority)) }}</x-ui.badge>
                            </span>
                        </div>
                        @if ($item->description)
                            <p class="mobile-row-sub">{{ \Illuminate\Support\Str::limit($item->description, 120) }}</p>
                        @endif
                        <form method="POST" action="{{ route('learn.sprint-planning.move-item', [$project, $item->id]) }}" style="margin-top:var(--sp-sm);">
                            @csrf
                            <input type="hidden" name="sprint_id" value="{{ $planning->sprintId }}">
                            <x-ui.button type="submit" severity="secondary" icon="arrow_forward">Move to Sprint</x-ui.button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>

    <x-forms.form-section title="This Sprint ({{ count($planning->sprintItems) }})">
        @if (empty($planning->sprintItems))
            <div class="empty-state">
                <x-ui.icon name="playlist_add" />
                <p>Move items in from the backlog.</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
                @foreach ($planning->sprintItems as $item)
                    <div class="mobile-row-card" style="cursor:default;">
                        <div class="mobile-row-top">
                            <span>
                                <span class="mobile-row-title">{{ $item->title }}</span>
                                <x-ui.badge tone="info">{{ str_replace('_', ' ', ucfirst($item->priority)) }}</x-ui.badge>
                            </span>
                        </div>
                        @if ($item->description)
                            <p class="mobile-row-sub">{{ \Illuminate\Support\Str::limit($item->description, 120) }}</p>
                        @endif
                        <form method="POST" action="{{ route('learn.sprint-planning.return-item', [$project, $item->id]) }}" style="margin-top:var(--sp-sm);">
                            @csrf
                            <x-ui.button type="submit" severity="secondary" icon="arrow_back">Return to Backlog</x-ui.button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>
</div>

<form method="POST" action="{{ route('learn.sprint-planning.confirm', $project) }}" style="margin-top:var(--sp-2xl);">
    @csrf
    <input type="hidden" name="sprint_id" value="{{ $planning->sprintId }}">
    <x-ui.button type="submit" severity="primary" icon="rocket_launch" :disabled="empty($planning->sprintItems)">Confirm Sprint</x-ui.button>
</form>
@endsection
