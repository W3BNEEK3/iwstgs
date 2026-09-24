@extends('layouts.shells.dashboard')

@section('title', 'Projects — ' . config('app.name', 'IWSTGS'))

@section('body')
<h1 style="margin-bottom:var(--sp-2xl);">Projects</h1>

@if (empty($projects))
    <div class="empty-state">
        <x-ui.icon name="inventory_2" :size="32" />
        <p>No projects are available yet.</p>
    </div>
@else
    {{-- Cards, not table rows — reflow into however many columns fit rather than
         stretching full-width, so a growing catalogue reads as a grid to scan
         instead of a long scroll (2-3 columns on desktop, 1 on mobile, no
         explicit breakpoints needed). --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:var(--sp-lg);">
        @foreach ($projects as $project)
            <a href="{{ route('learn.catalogue.show', $project->id()) }}" style="display:flex;flex-direction:column;text-decoration:none;color:inherit;border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--sp-xl);">
                <h2 style="font-size:var(--text-lg);margin-bottom:var(--sp-xs);">{{ $project->title() }}</h2>
                @if ($project->tagline())
                    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-sm);flex:1;">{{ $project->tagline() }}</p>
                @endif
                <x-ui.badge tone="neutral" style="align-self:flex-start;">{{ ucfirst($project->difficultyLevel()) }}</x-ui.badge>
            </a>
        @endforeach
    </div>
@endif
@endsection
