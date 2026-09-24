@extends('admin.layouts.admin')

@section('title', 'New Project')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.projects.index') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to projects
    </a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">New Project Template</h1>

<form method="POST" action="{{ route('admin.projects.store') }}">
    @csrf

    <x-forms.form-section title="Basics">
        <x-forms.text-input name="title" label="Title" :value="old('title')" required autofocus />
        <div class="field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="project_type" label="Project Type" :value="old('project_type')" help="e.g. web_application" required />
            <x-forms.select name="difficulty_level" label="Difficulty" required :options="[
                'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced',
            ]" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="tagline" label="Tagline" :value="old('tagline')" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="business_domain" label="Business Domain" :value="old('business_domain')" help="e.g. healthcare" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="business_context" label="Business Context" :value="old('business_context')" required :rows="6" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            {{-- specialization_tags[] — one input for now; a repeatable sub-form is future work --}}
            <x-forms.text-input name="specialization_tags[]" label="Specialization Tag" :value="old('specialization_tags.0')" help="e.g. backend — matches against role definitions for eligibility" required />
        </div>
    </x-forms.form-section>

    <x-ui.button type="submit" severity="primary" icon="check">Create Project</x-ui.button>
</form>
@endsection
