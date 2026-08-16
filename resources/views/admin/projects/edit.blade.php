@extends('admin.layouts.admin')

@section('title', 'Edit Project')

@section('content')
<h1 style="margin-bottom:var(--sp-2xl);">Edit Project</h1>

<form method="POST" action="{{ route('admin.projects.update', $project->id()) }}">
    @csrf
    @method('PATCH')

    <x-forms.text-input name="title" label="Title" :value="old('title', $project->title())" required />
    <x-forms.textarea name="business_context" label="Business Context" :value="old('business_context', $project->businessContext())" required :rows="6" />

    <x-ui.button type="submit" severity="primary" icon="check">Save changes</x-ui.button>
</form>

<p style="margin-top:var(--sp-2xl);color:var(--text-muted);font-size:var(--text-sm);">
    Type, tagline, difficulty, and specialization tags aren't editable from this form yet — only title and business context are currently wired to <code>UpdateProjectCommand</code>.
</p>

<p style="margin-top:var(--sp-lg);">
    <a href="{{ route('admin.projects.index') }}">&larr; Back to projects</a>
</p>
@endsection
