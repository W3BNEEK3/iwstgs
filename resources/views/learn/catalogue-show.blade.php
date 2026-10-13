@extends('layouts.shells.dashboard')

@section('title', $project->title . ' — ' . config('app.name', 'Areyna'))

@section('body')
<p style="margin-bottom:var(--sp-xl);">
    <a href="{{ route('learn.catalogue') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to catalogue
    </a>
</p>

<h1 style="margin-bottom:var(--sp-xs);">{{ $project->title }}</h1>
@if ($project->tagline)
    <p style="color:var(--text-muted);font-size:var(--text-base);margin:0 0 var(--sp-md);"><em>{{ $project->tagline }}</em></p>
@endif
<p style="margin-bottom:var(--sp-lg);white-space:pre-line;">{{ $project->businessContext }}</p>
<x-ui.badge tone="neutral" style="margin-bottom:var(--sp-2xl);">{{ ucfirst($project->difficultyLevel) }}</x-ui.badge>

@if ($explainer)
    @include('guidance.project-explainer', ['explainer' => $explainer])
    <div style="margin-top:var(--sp-xl);"></div>
@endif

@if ($project->isEnrolled)
    <x-forms.form-section title="You're enrolled">
        <p style="color:var(--text-muted);margin-bottom:var(--sp-lg);">
            Session status: <x-ui.badge tone="info">{{ ucfirst($project->sessionStatus) }}</x-ui.badge>
        </p>
        {{-- Sprint planning is a safe universal entry point: it bounces you to onboarding
             if induction isn't done yet, or to the sprint board if a sprint is already
             confirmed, so this one link always lands you where you actually are. --}}
        <x-ui.button :href="! $isBuild ? route('learn.sprint-planning', $project->id) : ($project->sessionStatus === 'diagnostic' ? route('learn.onboarding', $project->id) : route('learn.build', $project->id))" severity="primary" icon="arrow_forward">Continue</x-ui.button>
    </x-forms.form-section>
@elseif ($isBuild)
    <x-forms.form-section title="Choose your stack">
        <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0 0 var(--sp-lg);">
            You build the same app with the same steps in any of these. A harder stack adds more moving parts to understand, not a different project.
        </p>
        @if (empty($variants))
            <p style="color:var(--text-muted);">No stacks are available for this project yet.</p>
        @else
            <form method="POST" action="{{ route('learn.catalogue.enrol', $project->id) }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:var(--sp-md);margin-bottom:var(--sp-xl);">
                    @foreach ($variants as $variant)
                        @php
                            $open = $variant->isOpenTo($rank?->rankTier ?? 'Junior', $rank?->rankLevel ?? 1);
                        @endphp
                        <label style="display:flex;align-items:flex-start;gap:var(--sp-sm);padding:var(--sp-md);border:1px solid var(--border);border-radius:var(--radius-md);{{ ! $open ? 'opacity:.55;' : '' }}">
                            <input type="radio" name="stack_variant_id" value="{{ $variant->id }}" style="margin-top:3px;" @disabled(! $open) @checked($open && $loop->first) required>
                            <span>
                                <strong>{{ $variant->name }}</strong>
                                <x-ui.badge tone="neutral">{{ $variant->difficulty <= 1 ? 'Easier' : 'Harder' }}</x-ui.badge>
                                <span style="display:block;color:var(--text-muted);font-size:var(--text-xs);">{{ implode(', ', $variant->languages) }}@if ($variant->setupNotes) · {{ $variant->setupNotes }}@endif</span>
                                @unless ($open)
                                    <span style="display:block;color:var(--text-muted);font-size:var(--text-xs);">Unlocks at {{ $variant->rankGateLabel() }}</span>
                                @endunless
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('stack_variant_id')
                    <p class="field-error" style="margin:0 0 var(--sp-md);">{{ $message }}</p>
                @enderror
                <p style="color:var(--text-muted);font-size:var(--text-xs);margin:0 0 var(--sp-md);">You'll need a free GitHub account. Your code lives in your own public repository.</p>
                <x-ui.button type="submit" severity="primary" icon="check">Start building</x-ui.button>
            </form>
        @endif
    </x-forms.form-section>
@else
    <x-forms.form-section title="Choose a role">
        @if (empty($project->roleOptions))
            <p style="color:var(--text-muted);">No roles are currently configured for this project.</p>
        @else
            <form method="POST" action="{{ route('learn.catalogue.enrol', $project->id) }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:var(--sp-md);margin-bottom:var(--sp-xl);">
                    @foreach ($project->roleOptions as $role)
                        <label style="display:flex;align-items:flex-start;gap:var(--sp-sm);padding:var(--sp-md);border:1px solid var(--border);border-radius:var(--radius-md);{{ !$role->isEligible ? 'opacity:.55;' : '' }}">
                            <input type="radio" name="role_id" value="{{ $role->roleId }}" style="margin-top:3px;" @disabled(!$role->isEligible) required>
                            <span>
                                <strong>{{ $role->title }}</strong>
                                @if ($role->isLeadRole)
                                    <x-ui.badge tone="info">Lead role</x-ui.badge>
                                @endif
                                @if (!$role->isEligible)
                                    <span style="display:block;color:var(--text-muted);font-size:var(--text-xs);">Requires {{ $role->minYearsExperience }}+ years experience</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-ui.button type="submit" severity="primary" icon="check">Enrol</x-ui.button>
            </form>
        @endif
    </x-forms.form-section>
@endif
@endsection
