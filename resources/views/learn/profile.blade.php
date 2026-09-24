@extends('layouts.shells.dashboard')

@section('title', 'My Profile — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">My Profile</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Your competency graph, rank history, and session record.</p>
</div>

<div class="profile-grid-2">
    <x-forms.form-section title="Current Rank">
        <div style="display:flex;align-items:center;gap:var(--sp-md);margin-bottom:var(--sp-md);">
            <x-ui.badge tone="info">
                <x-ui.icon name="military_tech" :size="14" /> {{ $dashboard->rankTier }}-{{ $dashboard->rankLevel }}
            </x-ui.badge>
        </div>
        <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;">
            <x-ui.badge tone="neutral">Complexity: {{ ucfirst($dashboard->cacComplexity) }}</x-ui.badge>
            <x-ui.badge tone="neutral">Autonomy: {{ ucfirst($dashboard->cacAutonomy) }}</x-ui.badge>
            <x-ui.badge tone="neutral">Context Fidelity: {{ ucfirst($dashboard->cacContextFidelity) }}</x-ui.badge>
        </div>
        <div style="margin-top:var(--sp-md);">
            <x-ui.button :href="route('learn.profile.qualification')" severity="secondary" icon="verified">Role Qualification Report</x-ui.button>
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="Competency Graph">
        @php
            $center = 150;
            $maxRadius = 105;
            $tierValues = ['untested' => 0, 'basic' => 1, 'intermediate' => 2, 'advanced' => 3];
            $tierTones = ['untested' => 'neutral', 'basic' => 'warning', 'intermediate' => 'info', 'advanced' => 'success'];
            $count = max(count($dashboard->dimensions), 1);
            $angleStep = 360 / $count;

            $axisPoints = [];
            $dataCoords = [];
            foreach ($dashboard->dimensions as $i => $dim) {
                $angleRad = deg2rad(-90 + $i * $angleStep);
                $labelR = $maxRadius + 22;
                $axisPoints[] = [
                    'x' => $center + $maxRadius * cos($angleRad),
                    'y' => $center + $maxRadius * sin($angleRad),
                    'labelX' => $center + $labelR * cos($angleRad),
                    'labelY' => $center + $labelR * sin($angleRad),
                    'label' => $dim->shortLabel,
                ];
                $value = ($tierValues[$dim->tier] ?? 0) / 3;
                $dataCoords[] = ($center + $maxRadius * $value * cos($angleRad)) . ',' . ($center + $maxRadius * $value * sin($angleRad));
            }
            $polygonPoints = implode(' ', $dataCoords);
        @endphp
        <svg viewBox="0 0 300 300" data-rotate-on-scroll style="width:100%;max-width:320px;display:block;margin:0 auto;transform-origin:center;will-change:transform;">
            @for ($ring = 1; $ring <= 3; $ring++)
                <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $maxRadius * $ring / 3 }}" fill="none" stroke="var(--border)" stroke-width="1" />
            @endfor
            @foreach ($axisPoints as $p)
                <line x1="{{ $center }}" y1="{{ $center }}" x2="{{ $p['x'] }}" y2="{{ $p['y'] }}" stroke="var(--border)" stroke-width="1" />
            @endforeach
            <polygon points="{{ $polygonPoints }}" fill="var(--info)" fill-opacity="0.25" stroke="var(--info)" stroke-width="2" />
            @foreach ($axisPoints as $p)
                <text x="{{ $p['labelX'] }}" y="{{ $p['labelY'] }}" font-size="10" fill="var(--text-muted)" text-anchor="middle" dominant-baseline="middle">{{ $p['label'] }}</text>
            @endforeach
        </svg>
        <div style="display:flex;flex-wrap:wrap;gap:var(--sp-xs);justify-content:center;margin-top:var(--sp-sm);">
            @foreach ($dashboard->dimensions as $dim)
                <x-ui.badge :tone="$tierTones[$dim->tier] ?? 'neutral'">{{ $dim->shortLabel }}: {{ ucfirst($dim->tier) }}</x-ui.badge>
            @endforeach
        </div>
    </x-forms.form-section>
</div>

<div class="profile-grid-2">
    <x-forms.form-section title="Rank History">
        @if (empty($dashboard->rankEvents))
            <p style="color:var(--text-muted);font-size:var(--text-sm);">No rank changes recorded yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
                @foreach (array_reverse($dashboard->rankEvents) as $event)
                    <div style="border-left:2px solid var(--info);padding-left:var(--sp-sm);">
                        <div style="font-size:var(--text-sm);font-weight:600;">
                            {{ str_replace('_', ' ', ucfirst($event->eventType)) }}
                            @if ($event->fromRankTier)
                                — {{ $event->fromRankTier }}-{{ $event->fromRankLevel }} → {{ $event->toRankTier }}-{{ $event->toRankLevel }}
                            @else
                                — {{ $event->toRankTier }}-{{ $event->toRankLevel }}
                            @endif
                        </div>
                        @if ($event->triggerReason)
                            <div style="font-size:var(--text-sm);color:var(--text-muted);">{{ $event->triggerReason }}</div>
                        @endif
                        <div style="font-size:12px;color:var(--text-muted);">{{ $event->createdAt }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>

    <x-forms.form-section title="Open Gap Flags">
        @php $openGaps = array_filter($dashboard->gapFlags, fn ($g) => ! $g->isResolved); @endphp
        @if (empty($openGaps))
            <p style="color:var(--text-muted);font-size:var(--text-sm);">No open gaps — nice.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
                @foreach ($openGaps as $gap)
                    <x-ui.badge tone="warning">{{ $gap->dimensionId }}</x-ui.badge>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>
</div>

<div class="profile-grid-2">
    <x-forms.form-section title="Concept Mastery">
        @if (empty($dashboard->conceptMastery))
            <p style="color:var(--text-muted);font-size:var(--text-sm);">No concepts encountered yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach ($dashboard->conceptMastery as $concept)
                    @php
                        $conceptTone = match ($concept->status) {
                            'mastered' => 'success', 'partially_met' => 'warning', 'encountered' => 'neutral', default => 'neutral',
                        };
                    @endphp
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:var(--text-sm);">{{ $concept->conceptId }}</span>
                        <x-ui.badge :tone="$conceptTone">{{ str_replace('_', ' ', ucfirst($concept->status)) }}</x-ui.badge>
                    </div>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>

    <x-forms.form-section title="Session History">
        @if (empty($dashboard->sessions))
            <p style="color:var(--text-muted);font-size:var(--text-sm);">No project sessions yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
                @foreach ($dashboard->sessions as $session)
                    <a href="{{ route('learn.profile.session', $session->sessionId) }}" style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;color:inherit;">
                        <span style="font-size:var(--text-sm);">{{ $session->projectTitle }}</span>
                        <x-ui.badge tone="neutral">{{ ucfirst($session->status) }}</x-ui.badge>
                    </a>
                @endforeach
            </div>
        @endif
    </x-forms.form-section>
</div>

<x-forms.form-section title="Submission &amp; CAC Trajectory">
    @if (empty($dashboard->submissions))
        <p style="color:var(--text-muted);font-size:var(--text-sm);">No submissions yet.</p>
    @else
        <div class="table-frame" style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:var(--text-sm);">
                <thead>
                    <tr style="text-align:left;color:var(--text-muted);text-transform:uppercase;font-size:12px;">
                        <th style="padding:6px 10px;">Submitted</th>
                        <th style="padding:6px 10px;">Attempt</th>
                        <th style="padding:6px 10px;">Rank at Submission</th>
                        <th style="padding:6px 10px;">Complexity</th>
                        <th style="padding:6px 10px;">Autonomy</th>
                        <th style="padding:6px 10px;">Context</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_reverse($dashboard->submissions) as $submission)
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:6px 10px;">{{ $submission->submittedAt }}</td>
                            <td style="padding:6px 10px;">{{ $submission->attemptNumber }}</td>
                            <td style="padding:6px 10px;">{{ $submission->rankAtSubmission }}</td>
                            <td style="padding:6px 10px;">{{ ucfirst($submission->cacComplexityAtSub) }}</td>
                            <td style="padding:6px 10px;">{{ ucfirst($submission->cacAutonomyAtSub) }}</td>
                            <td style="padding:6px 10px;">{{ ucfirst($submission->cacContextAtSub) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-forms.form-section>
@endsection
