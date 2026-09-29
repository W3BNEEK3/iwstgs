@extends('admin.layouts.admin')

@section('title', 'Guide — Announcements')

@section('content')
@include('admin.guide._tabs')

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:var(--sp-lg);flex-wrap:wrap;margin-bottom:var(--sp-lg);">
    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;max-width:680px;">
        Tell learners about new <strong>learner-facing</strong> features (never admin-only changes). Write rough notes, let the AI draft a short
        learner summary, edit it, then publish. Each learner sees it once as a Tiroco card, and it stays on their What's new page.
        Learners who joined after it was published don't get the pop-up.
    </p>
    <x-ui.button :href="route('admin.guide.announcements.create')" icon="add">New announcement</x-ui.button>
</div>

@if ($announcements === [])
    <div class="empty-state">
        <x-ui.icon name="campaign" :size="32" />
        <p>No announcements yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Announcement</th><th>Status</th><th>Published</th><th></th></tr></thead>
            <tbody>
                @foreach ($announcements as $a)
                    <tr>
                        <td style="max-width:480px;">
                            <strong>{{ $a->title ?? $a->internalTitle }}</strong>
                            @if ($a->title) <div style="font-size:var(--text-xs);color:var(--text-muted);">{{ $a->internalTitle }}</div> @endif
                            @if ($a->body) <div style="font-size:var(--text-sm);color:var(--text-muted);">{{ $a->body }}</div> @endif
                        </td>
                        <td>
                            <x-ui.badge :tone="match ($a->status) { 'published' => 'success', 'archived' => 'neutral', default => 'warning' }">{{ ucfirst($a->status) }}</x-ui.badge>
                            @if ($a->featureFlag) <div style="font-size:var(--text-xs);color:var(--text-muted);">only while <code>{{ $a->featureFlag }}</code> is on</div> @endif
                        </td>
                        <td style="font-size:var(--text-sm);white-space:nowrap;">{{ $a->publishedAt ? \Illuminate\Support\Carbon::parse($a->publishedAt)->format('j M Y') : '—' }}</td>
                        <td><x-ui.button :href="route('admin.guide.announcements.edit', $a->id)" severity="secondary" icon="edit">Open</x-ui.button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
