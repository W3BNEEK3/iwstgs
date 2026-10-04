@extends('layouts.shells.dashboard')

@section('title', 'Projects — ' . config('app.name', 'Areyna'))

@php
    $trackInfo = [
        'build' => ['Build', 'Make a real app from an empty repository, step by step, in your own GitHub account. You finish with software you own.', 'construction'],
        'work_experience' => ['Work experience', 'Join a team at a company: tickets, teammates\' pull requests, deadlines and changing requirements.', 'work'],
        'classic' => ['Challenges', 'Self-contained workplace scenarios: you submit your work here and get reviewed.', 'assignment'],
    ];
    $showHeadings = count($groups) > 1;
@endphp

@section('body')
<h1 style="margin-bottom:var(--sp-2xl);">Projects</h1>

@if (empty($projects))
    <div class="empty-state">
        <x-ui.icon name="inventory_2" :size="32" />
        <p>No projects are available yet.</p>
    </div>
@else
    @foreach ($groups as $track => $trackProjects)
        <section style="margin-bottom:var(--sp-2xl);" @if ($showHeadings) aria-labelledby="track-{{ $track }}" @endif>
            @if ($showHeadings)
                <h2 id="track-{{ $track }}" style="display:flex;align-items:center;gap:var(--sp-sm);font-size:var(--text-lg);margin:0 0 var(--sp-xs);">
                    <x-ui.icon :name="$trackInfo[$track][2]" :size="20" /> {{ $trackInfo[$track][0] }}
                </h2>
                <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-lg);">{{ $trackInfo[$track][1] }}</p>
            @endif
            {{-- Cards, not table rows — reflow into however many columns fit rather than
                 stretching full-width, so a growing catalogue reads as a grid to scan. --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:var(--sp-lg);">
                @foreach ($trackProjects as $project)
                    <a href="{{ route('learn.catalogue.show', $project->id()) }}" style="display:flex;flex-direction:column;text-decoration:none;color:inherit;border:1px solid var(--border);border-radius:var(--radius-md);padding:var(--sp-xl);">
                        <h3 style="font-size:var(--text-lg);margin:0 0 var(--sp-xs);">{{ $project->title() }}</h3>
                        @if ($project->tagline())
                            <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-sm);flex:1;">{{ $project->tagline() }}</p>
                        @endif
                        <div style="display:flex;gap:var(--sp-xs);flex-wrap:wrap;">
                            <x-ui.badge tone="neutral">{{ ucfirst($project->difficultyLevel()) }}</x-ui.badge>
                            @foreach ($variants[$project->id()] ?? [] as $variant)
                                <x-ui.badge tone="info">{{ $variant->name }}</x-ui.badge>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
@endif
@endsection
