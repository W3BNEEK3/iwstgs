@extends('layouts.shells.dashboard')

@section('title', $summary->projectTitle . ' — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">{{ $summary->projectTitle }}</h1>
    <x-ui.badge tone="neutral">{{ ucfirst($summary->status) }}</x-ui.badge>
</div>

<x-forms.form-section title="Submissions in this session">
    @if (empty($summary->submissions))
        <p style="color:var(--text-muted);font-size:var(--text-sm);">No submissions in this session yet.</p>
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
                    @foreach ($summary->submissions as $submission)
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

<div style="margin-top:var(--sp-xl);">
    <x-ui.button :href="route('learn.profile')" severity="secondary" icon="arrow_back">Back to Profile</x-ui.button>
</div>
@endsection
