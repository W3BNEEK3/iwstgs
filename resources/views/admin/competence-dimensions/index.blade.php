@extends('admin.layouts.admin')

@section('title', 'Competency Model')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-2xl);">
    <div>
        <h1>Competency Model</h1>
        <p style="color:var(--text-muted);font-size:var(--text-sm);margin:var(--sp-xs) 0 0;">The canonical dimensions every submission is evaluated against, and where each one shows up as a task-specific criterion.</p>
    </div>
    <x-ui.button severity="primary" :href="route('admin.competence-dimensions.create')" icon="add">Add Dimension</x-ui.button>
</div>

@if (empty($dimensions))
    <div class="empty-state">
        <x-ui.icon name="category" :size="32" />
        <p>No competency dimensions defined yet.</p>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(380px, 1fr));gap:var(--sp-xl);">
        @foreach ($dimensions as $dimension)
            <div style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--sp-xl);background:var(--bg);display:flex;flex-direction:column;gap:var(--sp-md);">
                
                {{-- Card Header --}}
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--sp-sm);">
                    <div>
                        <h2 style="font-size:var(--text-lg);margin:0;line-height:1.2;">{{ $dimension->sequenceOrder }} &middot; {{ $dimension->name }}</h2>
                        <code style="font-size:var(--text-xs);color:var(--text-muted);display:block;margin-top:var(--sp-xs);">{{ $dimension->id }}</code>
                    </div>
                    <div style="flex:none;">
                        <x-ui.badge tone="info">{{ $dimension->shortLabel }}</x-ui.badge>
                    </div>
                </div>

                {{-- Core Question --}}
                <p style="margin:0;font-size:var(--text-sm);color:var(--text);line-height:1.5;">
                    {{ $dimension->coreQuestion }}
                </p>

                {{-- Observable Indicators --}}
                @if (!empty($dimension->observableIndicators))
                    <div style="display:flex;flex-wrap:wrap;gap:var(--sp-xs);">
                        @foreach ($dimension->observableIndicators as $indicator)
                            <x-ui.badge tone="neutral">{{ $indicator }}</x-ui.badge>
                        @endforeach
                    </div>
                @endif

                <hr style="border:0;border-top:1px solid var(--border);margin:var(--sp-xs) 0;" />

                {{-- Progressive Disclosure: Accordion for Criteria --}}
                @php $taskCriteria = $criteriaByDimension[$dimension->id] ?? []; @endphp
                <details style="font-size:var(--text-sm);">
                    <summary style="cursor:pointer;user-select:none;font-weight:500;color:var(--text);display:flex;align-items:center;gap:var(--sp-xs);">
                        <span>View Task-Specific Criteria ({{ count($taskCriteria) }})</span>
                    </summary>
                    <div style="margin-top:var(--sp-md);display:flex;flex-direction:column;gap:var(--sp-xs);">
                        @if (empty($taskCriteria))
                            <p style="color:var(--text-muted);margin:0;font-style:italic;">No task has a criterion under this dimension yet.</p>
                        @else
                            @foreach ($taskCriteria as $criterion)
                                <a href="{{ route('admin.tasks.criteria.index', $criterion['taskId']) }}" 
                                   style="display:flex;flex-direction:column;gap:var(--sp-xs);text-decoration:none;color:inherit;padding:var(--sp-sm);border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-subtle);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;">
                                        <strong style="color:var(--text-strong);">{{ $criterion['taskDimensionLabel'] }}</strong>
                                        <x-ui.badge tone="neutral">{{ ucfirst($criterion['complexityLevel']) }}</x-ui.badge>
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;color:var(--text-muted);font-size:var(--text-xs);">
                                        <span>{{ $criterion['taskTitle'] }}</span>
                                        <span class="mono">w={{ $criterion['weight'] }}</span>
                                    </div>
                                </a>
                            @endforeach
                        @endif
                    </div>
                </details>
                
                {{-- Edit Action --}}
                <div style="margin-top:auto;padding-top:var(--sp-sm);display:flex;justify-content:flex-end;">
                    <x-ui.button severity="secondary" :href="route('admin.competence-dimensions.index', ['edit' => $dimension->id])" icon="edit">
                        Edit
                    </x-ui.button>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
