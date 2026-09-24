@extends('layouts.shells.auth')

@section('title', 'System Administration — ' . config('app.name', 'Areyna'))

@section('body')
<div style="text-align:center;margin-bottom:var(--sp-2xl);">
    <span class="brand-mark" style="margin:0 auto var(--sp-md);">
        <x-ui.icon name="admin_panel_settings" :size="18" />
    </span>
    <h1 style="font-size:var(--text-xl);margin-bottom:var(--sp-xs);">System Administration</h1>
    <p style="color:var(--text-muted);font-size:var(--text-xs);margin:0;">Restricted access. Authorised personnel only.</p>
</div>

<form method="POST" action="{{ route('sys.login.store') }}">
    @csrf

    <x-forms.text-input name="email" label="Email address" type="email" autocomplete="off" required autofocus />
    <x-forms.text-input name="password" label="Password" type="password" autocomplete="off" required />

    <x-ui.button type="submit" severity="primary" style="width:100%;justify-content:center;margin-top:var(--sp-lg);">Access System</x-ui.button>
</form>

<p style="text-align:center;margin-top:var(--sp-xl);font-size:var(--text-xs);color:var(--text-muted);">
    Organisation admin? <a href="{{ route('login') }}">Sign in here</a>
</p>
@endsection
