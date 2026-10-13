@extends('layouts.shells.dashboard')

@section('title', 'Settings — ' . config('app.name', 'Areyna'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Settings</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Account details, appearance and the in-app guide.</p>
</div>

<x-forms.form-section title="Account">
    <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
        <div>
            <div style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:2px;">Name</div>
            <div>{{ $user->name }}</div>
        </div>
        <div>
            <div style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:2px;">Email</div>
            <div>{{ $user->email }}</div>
        </div>
    </div>
</x-forms.form-section>

<div style="margin-top:var(--sp-xl);"></div>
<x-forms.form-section title="Appearance">
    <div style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:var(--sp-sm);">Theme</div>
    <div class="theme-switch">
        <button type="button" data-theme-choice="light">Light</button>
        <button type="button" data-theme-choice="dark">Dark</button>
        <button type="button" data-theme-choice="system">System</button>
    </div>
</x-forms.form-section>

@if ($githubOn)
    <div style="margin-top:var(--sp-xl);"></div>
    <x-forms.form-section title="GitHub" id="github">
        <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0 0 var(--sp-md);">
            Build projects live in your own GitHub repository. Areyna only reads the repositories you give the Areyna app access to.
        </p>
        @if ($github)
            <p style="margin:0 0 var(--sp-md);">Connected as <strong>{{ $github->githubLogin }}</strong></p>
            <form method="POST" action="{{ route('github.disconnect') }}" onsubmit="return confirm('Disconnect GitHub? Your builds pause until you reconnect.')">
                @csrf
                <x-ui.button type="submit" severity="secondary" icon="link_off">Disconnect GitHub</x-ui.button>
            </form>
        @else
            <x-ui.button :href="route('github.connect', ['return' => '/learn/settings'])" severity="secondary" icon="link">Connect GitHub</x-ui.button>
        @endif
    </x-forms.form-section>
@endif

<div style="margin-top:var(--sp-xl);"></div>
<x-forms.form-section title="Guide" id="guide">
    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0 0 var(--sp-md);">
        Tiroco, your guide, explains each part of the platform the first time you reach it, and keeps an eye on how you're doing:
        a hint when you seem stuck, a tip at a quiet moment, an outside resource for a skill that keeps tripping you up, and a short note when something new arrives.
        The lightbulb in the corner of every page opens that page's tips any time.
    </p>
    <p style="margin:0 0 var(--sp-md);">
        Status: <x-ui.badge :tone="$guide->isEnabled ? 'success' : 'neutral'">{{ $guide->isEnabled ? 'On' : 'Off' }}</x-ui.badge>
    </p>

    @if ($guide->isEnabled && $guideKinds !== [])
        <form method="POST" action="{{ route('guide.kinds') }}" style="margin-bottom:var(--sp-lg);">
            @csrf
            <fieldset style="border:none;padding:0;margin:0 0 var(--sp-md);display:flex;flex-direction:column;gap:var(--sp-sm);">
                <legend style="font-size:var(--text-sm);font-weight:600;margin-bottom:var(--sp-sm);">Tiroco may pop up with</legend>
                @foreach ($guideKinds as $kind)
                    <label style="display:flex;align-items:center;gap:var(--sp-sm);font-size:var(--text-sm);">
                        <input type="checkbox" name="kinds[]" value="{{ $kind->value }}" @checked(! $guide->isMuted($kind))>
                        {{ $kind->switchLabel() }}
                    </label>
                @endforeach
            </fieldset>
            <x-ui.button type="submit" severity="secondary" icon="save">Save guide preferences</x-ui.button>
        </form>
    @endif

    <div class="btn-row">
        @unless ($guide->isEnabled)
            <form method="POST" action="{{ route('guide.enable') }}">
                @csrf
                <x-ui.button type="submit" severity="primary" icon="lightbulb">Turn the guide back on</x-ui.button>
            </form>
        @endunless
        <form method="POST" action="{{ route('guide.reset') }}">
            @csrf
            <x-ui.button type="submit" severity="secondary" icon="replay">Show all tips again</x-ui.button>
        </form>
        @if ($announcementsOn)
            <a href="{{ route('guide.whats-new') }}" style="text-decoration:none;">
                <x-ui.button severity="secondary" icon="campaign">What's new</x-ui.button>
            </a>
        @endif
    </div>
</x-forms.form-section>
@endsection
