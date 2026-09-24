@extends('admin.layouts.admin')

@section('title', 'Edit Scenario')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.projects.scenarios.index', $project->id()) }}">&larr; Back to scenarios</a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">Edit Scenario</h1>

<form method="POST" action="{{ route('admin.projects.scenarios.update', [$project->id(), $scenario->id()]) }}">
    @csrf
    @method('PATCH')

    <x-forms.text-input name="title" label="Title" :value="old('title', $scenario->title())" required />

    <x-ui.button type="submit" severity="primary" icon="check" style="margin-top:var(--sp-lg);">Save changes</x-ui.button>
</form>

<p style="margin:var(--sp-lg) 0 var(--sp-2xl);color:var(--text-muted);font-size:var(--text-sm);">
    Narrative context, situation trigger, and calibration aren't editable after creation yet — only the title is currently wired to <code>UpdateScenarioCommand</code>.
</p>

<x-forms.form-section title="Reference Materials">
    @forelse ($scenario->referenceMaterials() as $material)
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:var(--sp-md);border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--sp-md);margin-bottom:var(--sp-sm);">
            <div>
                <x-ui.badge tone="neutral">{{ str_replace('_', ' ', $material->type()->value) }}</x-ui.badge>
                <strong style="margin-left:6px;">{{ $material->title() }}</strong>
                <p style="color:var(--text-muted);font-size:var(--text-sm);margin:6px 0 0;">{{ \Illuminate\Support\Str::limit($material->content(), 140) }}</p>
            </div>
            <x-ui.button severity="destructive" type="button" data-modal-open="remove-material-{{ $material->id() }}">Remove</x-ui.button>
        </div>
        <x-overlays.confirm-modal
            :id="'remove-material-' . $material->id()"
            title="Remove reference material?"
            description="This can't be undone."
            :action="route('admin.scenarios.materials.remove', [$scenario->id(), $material->id()])"
            method="DELETE"
            confirmLabel="Remove"
        />
    @empty
        <p style="color:var(--text-muted);">No reference materials yet.</p>
    @endforelse

    <div style="margin-top:var(--sp-xl);padding-top:var(--sp-xl);border-top:1px solid var(--border);">
        <div class="form-section-title">Add material</div>
        <form method="POST" action="{{ route('admin.scenarios.materials.add', $scenario->id()) }}">
            @csrf
            <div class="field-grid">
                <x-forms.select name="material_type" label="Type" required :options="[
                    'document' => 'Document', 'email' => 'Email', 'slack_message' => 'Slack Message',
                    'ticket' => 'Ticket', 'report' => 'Report', 'notes' => 'Notes',
                ]" />
                <x-forms.text-input name="material_title" label="Title" required />
            </div>
            <div style="margin-top:var(--sp-lg);">
                <x-forms.textarea name="content" label="Content" required :rows="4" />
            </div>
            <x-ui.button type="submit" severity="secondary" icon="add" style="margin-top:var(--sp-lg);">Add material</x-ui.button>
        </form>
    </div>
</x-forms.form-section>
@endsection
