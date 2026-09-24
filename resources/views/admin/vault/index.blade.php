@extends('admin.layouts.admin')

@section('title', 'Vault Items — ' . $project->title())

@section('content')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.projects.index') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to projects
    </a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">Artifact Vault — {{ $project->title() }}</h1>

<x-forms.form-section title="Add Vault Item">
    @include('admin.vault._form', ['projectId' => $projectId])
</x-forms.form-section>

<div style="margin-top:var(--sp-2xl);">
    <h2 style="font-size:var(--text-lg);margin-bottom:var(--sp-lg);">Current Vault Items</h2>
    @include('admin.vault._list', ['items' => $items, 'projectId' => $projectId])
</div>
@endsection
