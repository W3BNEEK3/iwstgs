@extends('admin.layouts.admin')

@section('title', $resource ? 'Edit resource' : 'New resource')

@section('content')
@php
    $kinds = \Src\Guidance\Domain\Content\GuideResource::KINDS;
    $levels = \Src\Guidance\Domain\Content\GuideResource::LEVELS;
@endphp

<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('admin.guide.resources') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to resources
    </a>
</p>

<h1 style="margin-bottom:var(--sp-2xl);">{{ $resource ? 'Edit ' . $resource->name : 'New resource' }}</h1>

<form method="POST" action="{{ $resource ? route('admin.guide.resources.update', $resource->id) : route('admin.guide.resources.store') }}">
    @csrf
    @if ($resource) @method('PUT') @endif

    <x-forms.form-section title="Resource">
        <div class="field-grid">
            <x-forms.text-input name="name" label="Name" :value="$resource?->name" required maxlength="120" />
            <x-forms.text-input name="url" type="url" label="Link" :value="$resource?->url" required maxlength="500" />
        </div>
        <div style="margin-top:var(--sp-lg);">
            <x-forms.textarea name="blurb" label="One-line description" :value="$resource?->blurb" required :rows="2" help="Shown to learners and given to Tiroco as the reason to recommend it. Max 300 characters." />
        </div>
        <div class="field-grid" style="margin-top:var(--sp-lg);">
            <x-forms.select name="kind" label="Kind" :value="$resource?->kind" required :options="array_combine($kinds, array_map('ucfirst', $kinds))" />
            <x-forms.select name="level" label="Level" :value="$resource?->level ?? 'beginner'" required :options="array_combine($levels, array_map('ucfirst', $levels))" />
        </div>

        <fieldset class="field @if ($errors->has('dimensions')) has-error @endif" style="border:none;padding:0;margin:var(--sp-lg) 0 0;">
            <legend style="font-weight:600;font-size:var(--text-sm);margin-bottom:var(--sp-sm);">Helps with these skills</legend>
            @foreach ($dimensions as $id => $label)
                <label style="display:flex;align-items:center;gap:var(--sp-sm);font-size:var(--text-sm);margin-bottom:var(--sp-xs);">
                    <input type="checkbox" name="dimensions[]" value="{{ $id }}" @checked(in_array($id, old('dimensions', $resource?->dimensions ?? []), true))> {{ $label }}
                </label>
            @endforeach
            @error('dimensions') <span class="field-error">{{ $message }}</span> @enderror
        </fieldset>

        <div style="display:flex;gap:var(--sp-xl);margin-top:var(--sp-lg);font-size:var(--text-sm);">
            <label style="display:flex;align-items:center;gap:var(--sp-sm);">
                <input type="hidden" name="is_free" value="0"><input type="checkbox" name="is_free" value="1" @checked($resource?->isFree ?? true)> Free to use
            </label>
            <label style="display:flex;align-items:center;gap:var(--sp-sm);">
                <input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($resource?->isActive ?? true)> Active
            </label>
        </div>
    </x-forms.form-section>

    <div class="btn-row">
        <x-ui.button type="submit" severity="primary" icon="save">{{ $resource ? 'Save' : 'Add resource' }}</x-ui.button>
        @if ($resource)
            <x-ui.button type="submit" severity="destructive" icon="delete" form="delete-resource">Delete</x-ui.button>
        @endif
    </div>
</form>

@if ($resource)
    <form method="POST" action="{{ route('admin.guide.resources.destroy', $resource->id) }}" id="delete-resource" onsubmit="return confirm('Delete this resource? Learners who were recommended it keep their past messages.')">
        @csrf
        @method('DELETE')
    </form>
@endif
@endsection
