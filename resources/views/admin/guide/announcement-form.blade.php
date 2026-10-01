@extends('admin.layouts.admin')

@section('title', $announcement ? 'Announcement' : 'New announcement')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.guide.announcements') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to announcements
    </a>
</p>

<div style="display:flex;align-items:center;gap:var(--sp-md);flex-wrap:wrap;margin-bottom:var(--sp-2xl);">
    <h1 style="margin:0;">{{ $announcement ? $announcement->internalTitle : 'New announcement' }}</h1>
    @if ($announcement)
        <x-ui.badge :tone="match ($announcement->status) { 'published' => 'success', 'archived' => 'neutral', default => 'warning' }">{{ ucfirst($announcement->status) }}</x-ui.badge>
    @endif
</div>

<form method="POST" action="{{ $announcement ? route('admin.guide.announcements.update', $announcement->id) : route('admin.guide.announcements.store') }}">
    @csrf
    @if ($announcement) @method('PUT') @endif

    <x-forms.form-section title="For the team">
        <x-forms.text-input name="internal_title" label="Internal title" :value="$announcement?->internalTitle" required maxlength="150" help="Only admins see this." />
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="notes" label="What changed (notes)" :value="$announcement?->notes" required :rows="6" help="Rough or technical is fine. This is what the AI summarises for learners." />
        </div>
    </x-forms.form-section>

    <x-forms.form-section title="What learners see">
        <x-forms.text-input name="title" label="Title" :value="$announcement?->title" maxlength="80" help="About 8 words, starting with what learners can now do." />
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="body" label="Text" :value="$announcement?->body" :rows="3" help="About 40 words, max 400 characters. Plain words, no internal names." />
        </div>
        <div class="field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="link_url" label="Link (optional)" :value="$announcement?->linkUrl" placeholder="/learn/profile" help="A page inside Areyna, starting with /." />
            <x-forms.text-input name="feature_flag" label="Feature flag (optional)" :value="$announcement?->featureFlag" placeholder="reporting.learner_profile" help="Only shown while this flag is on." />
        </div>
    </x-forms.form-section>

    <div class="btn-row">
        <x-ui.button type="submit" severity="primary" icon="save">{{ $announcement ? 'Save' : 'Save draft' }}</x-ui.button>
    </div>
</form>

@if ($announcement)
    <div class="panel" style="margin-top:var(--sp-2xl);">
        <h2 style="margin:0 0 var(--sp-sm);font-size:var(--text-md);">Next steps</h2>
        <p style="margin:0 0 var(--sp-lg);font-size:var(--text-sm);color:var(--text-muted);">
            Save any edits first. Generating replaces the learner title and text with a fresh draft from your notes.
        </p>
        <div class="btn-row">
            <form method="POST" action="{{ route('admin.guide.announcements.generate', $announcement->id) }}">
                @csrf
                <x-ui.button type="submit" severity="secondary" icon="auto_awesome">Generate learner summary</x-ui.button>
            </form>
            @if ($announcement->status !== 'published')
                <form method="POST" action="{{ route('admin.guide.announcements.status', $announcement->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="published">
                    <x-ui.button type="submit" severity="primary" icon="campaign">Publish to learners</x-ui.button>
                </form>
            @endif
            @if ($announcement->status !== 'archived')
                <form method="POST" action="{{ route('admin.guide.announcements.status', $announcement->id) }}" onsubmit="return confirm('Archive? It disappears from pop-ups and the What\'s new page.')">
                    @csrf
                    <input type="hidden" name="status" value="archived">
                    <x-ui.button type="submit" severity="secondary" icon="archive">Archive</x-ui.button>
                </form>
            @endif
        </div>
    </div>
@endif
@endsection
