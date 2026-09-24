@extends('admin.layouts.admin')

@section('title', 'AI Engine — Concept Tag Suggestions')

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-md);margin-bottom:var(--sp-2xl);">
    <div>
        <h1 style="margin:0 0 var(--sp-xs);">Concept Tag Suggestions</h1>
        <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">
            Claude detected these concepts in distinguished submissions that are not in the existing knowledge anchor index.
            Approve to create a new concept tag, or dismiss with a note.
        </p>
    </div>
    <a href="{{ route('admin.ai-engine.index') }}" style="text-decoration:none;">
        <x-ui.button severity="secondary" icon="arrow_back">Event Log</x-ui.button>
    </a>
</div>

@if (session('success'))
    <x-ui.alert tone="success" style="margin-bottom:var(--sp-lg);">{{ session('success') }}</x-ui.alert>
@endif

@if (empty($recommendations))
    <div class="empty-state">
        <x-ui.icon name="lightbulb" :size="32" />
        <p>No pending concept tag suggestions. They appear here once learners achieve <em>distinguished</em> tier and Claude detects novel concepts in their work.</p>
    </div>
@else
    <div style="display:grid;gap:var(--sp-lg);">
        @foreach ($recommendations as $rec)
            <div class="panel" style="display:grid;gap:var(--sp-md);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--sp-md);flex-wrap:wrap;">
                    <div>
                        <p style="margin:0 0 var(--sp-xs);font-weight:600;font-size:var(--text-md);">
                            {{ $rec['proposedContent']['concept_name'] ?? '(unnamed)' }}
                        </p>
                        <p style="margin:0;font-size:var(--text-sm);color:var(--text-muted);">
                            Domain: <strong>{{ $rec['proposedContent']['domain'] ?? '—' }}</strong>
                            &nbsp;·&nbsp; Task: <strong>{{ $rec['taskTitle'] }}</strong>
                            &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($rec['createdAt'])->diffForHumans() }}
                        </p>
                    </div>
                    <x-ui.badge tone="warning">Pending Review</x-ui.badge>
                </div>

                <div style="background:var(--surface-subtle);border-radius:var(--radius-sm);padding:var(--sp-md);font-size:var(--text-sm);line-height:1.6;">
                    <strong>Why Claude suggested this:</strong><br>
                    {{ $rec['proposedContent']['reason'] ?? '—' }}
                </div>

                <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;">
                    {{-- Approve --}}
                    <form method="POST" action="{{ route('admin.ai-engine.recommendations.approve', $rec['id']) }}">
                        @csrf
                        <x-ui.button type="submit" severity="primary" icon="check_circle">Approve Concept</x-ui.button>
                    </form>

                    {{-- Dismiss --}}
                    <details style="display:contents;">
                        <summary style="list-style:none;cursor:pointer;">
                            <x-ui.button type="button" severity="secondary" icon="close">Dismiss</x-ui.button>
                        </summary>
                        <form method="POST" action="{{ route('admin.ai-engine.recommendations.dismiss', $rec['id']) }}"
                              style="display:flex;gap:var(--sp-sm);align-items:flex-end;margin-top:var(--sp-sm);width:100%;">
                            @csrf
                            <x-forms.textarea name="notes" label="Reason for dismissal (optional)" rows="2"
                                style="flex:1;" />
                            <x-ui.button type="submit" severity="danger">Confirm Dismiss</x-ui.button>
                        </form>
                    </details>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
