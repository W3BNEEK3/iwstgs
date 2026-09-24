@extends('admin.layouts.admin')

@section('title', 'Add Dimension')

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.competence-dimensions.index') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to competency model
    </a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">Add Dimension</h1>

<form method="POST" action="{{ route('admin.competence-dimensions.store') }}">
    @csrf

    <x-forms.form-section title="Dimension">
        <x-forms.text-input name="name" label="Name" help="Full name, e.g. 'Problem Analysis & Decomposition'." :value="old('name')" required />
        <div style="margin-top:var(--sp-lg);">
            <x-forms.text-input name="short_label" label="Short Label" help="Used on radar charts and badges — keep it brief." :value="old('short_label')" required />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="core_question" label="Core Question" help="The single question this dimension answers, e.g. 'Does the learner understand the problem before solving it?'" :value="old('core_question')" required :rows="2" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="observable_indicators" label="Observable Indicators" help="One per line — the concrete signals an evaluator looks for." :value="old('observable_indicators')" required :rows="4" />
        </div>
    </x-forms.form-section>

    <x-ui.button type="submit" severity="primary" icon="check">Create Dimension</x-ui.button>
</form>
@endsection
