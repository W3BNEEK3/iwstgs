@extends('admin.layouts.admin')

@section('title', 'Human Review Queue')

@section('content')
<h1 style="margin-bottom:var(--sp-xs);">Human Review Queue</h1>
<p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-2xl);">Evaluations Claude flagged as uncertain, waiting on a human decision.</p>

@if (empty($reviews))
    <div class="empty-state">
        <x-ui.icon name="fact_check" :size="32" />
        <p>No reviews pending.</p>
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:var(--sp-lg);">
        @foreach ($reviews as $review)
            <div style="border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--sp-lg);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-sm);">
                    <div>
                        <strong>{{ $review->taskTitle }}</strong>
                        <span style="color:var(--text-muted);font-size:var(--text-sm);"> — {{ $review->learnerName }}</span>
                    </div>
                    <x-ui.badge :tone="$review->status === 'pending' ? 'warning' : 'info'">{{ str_replace('_', ' ', ucfirst($review->status)) }}</x-ui.badge>
                </div>
                @if ($review->queuedAt)
                    <p style="color:var(--text-muted);font-size:var(--text-xs);margin:0 0 var(--sp-md);">Queued {{ $review->queuedAt }}</p>
                @endif

                @if ($review->status === 'pending')
                    <form method="POST" action="{{ route('admin.human-review.start', $review->id) }}">
                        @csrf
                        <x-ui.button type="submit" severity="primary" icon="play_arrow">Start Review</x-ui.button>
                    </form>
                @elseif ($review->status === 'in_review')
                    <form method="POST" action="{{ route('admin.human-review.resolve', $review->id) }}">
                        @csrf
                        <x-forms.select
                            name="decision"
                            label="Decision"
                            required
                            placeholder="Select..."
                            :options="[
                                'proficient' => 'Proficient — mark the task complete',
                                'not_proficient' => 'Not proficient — counts as a failed attempt',
                                'escalate' => 'Escalate — needs further attention',
                            ]"
                        />
                        <x-forms.textarea name="notes" label="Notes" :rows="3" />
                        <x-ui.button type="submit" severity="primary" icon="check" style="margin-top:var(--sp-md);">Resolve</x-ui.button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection
