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

<div style="margin-top:var(--sp-xl);"></div>
<x-forms.form-section title="Guide">
    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0 0 var(--sp-md);">
        Tiroco, your guide, shows short tips the first time you reach each part of the platform and when something new happens, like your first consequence card.
        The lightbulb in the corner of every page opens that page's tips any time.
    </p>
    <p style="margin:0 0 var(--sp-md);">
        Status: <x-ui.badge :tone="$guide->isEnabled ? 'success' : 'neutral'">{{ $guide->isEnabled ? 'On' : 'Off' }}</x-ui.badge>
    </p>
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
    </div>
</x-forms.form-section>
@endsection
