@extends('layouts.shells.auth')

@section('title', 'Log In — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="text-align:left;margin-bottom:var(--sp-2xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Log In</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Welcome back. Please enter your details.</p>
</div>

<form method="POST" action="{{ route('login') }}">
    @csrf

    <x-forms.text-input name="email" label="Email address" type="email" autocomplete="email" required autofocus />
    <x-forms.text-input name="password" label="Password" type="password" autocomplete="current-password" required />

    <div style="display:flex;justify-content:space-between;align-items:center;margin:var(--sp-lg) 0;">
        <label style="display:flex;align-items:center;gap:6px;font-size:var(--text-sm);font-weight:500;">
            <input type="checkbox" name="remember" value="1">
            Remember me
        </label>
        <a href="#" style="font-size:var(--text-sm);">Forgot password?</a>
    </div>

    <x-ui.button type="submit" severity="primary" style="width:100%;justify-content:center;">Sign in</x-ui.button>
</form>

<p style="text-align:center;margin-top:var(--sp-xl);font-size:var(--text-sm);color:var(--text-muted);">
    Don't have an account? <a href="{{ route('register') }}">Sign up</a>
</p>
@endsection
