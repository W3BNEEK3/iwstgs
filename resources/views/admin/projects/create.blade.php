@extends('admin.layouts.admin')
@section('title', 'New Project')
@section('content')
    <h1>New Project Template</h1>

    @if ($errors->any())
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    <form method="POST" action="{{ route('admin.projects.store') }}">
        @csrf
        <label>Title <input name="title" value="{{ old('title') }}" required></label>
        <label>Project type <input name="project_type" value="{{ old('project_type') }}" required></label>
        <label>Tagline <input name="tagline" value="{{ old('tagline') }}"></label>
        <label>Business domain <input name="business_domain" value="{{ old('business_domain') }}"></label>
        <label>Business context <textarea name="business_context" required>{{ old('business_context') }}</textarea></label>
        <label>Difficulty
            <select name="difficulty_level">
                <option value="beginner">beginner</option>
                <option value="intermediate">intermediate</option>
                <option value="advanced">advanced</option>
            </select>
        </label>
        {{-- specialization_tags[] — one input for now; a repeatable HTMX sub-form comes in 4b --}}
        <label>Specialization tag <input name="specialization_tags[]" value="{{ old('specialization_tags.0') }}" required></label>
        <button type="submit">Create Project</button>
    </form>
@endsection
