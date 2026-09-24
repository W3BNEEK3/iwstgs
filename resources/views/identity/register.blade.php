@extends('layouts.shells.auth')

@section('title', 'Sign Up — ' . config('app.name', 'Areyna'))

@section('body')
<div style="text-align:left;margin-bottom:var(--sp-2xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Create an account</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Join us to start learning and managing your tasks.</p>
</div>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <x-forms.text-input name="name" label="Full Name" type="text" required autofocus />
    <x-forms.text-input name="email" label="Email address" type="email" autocomplete="email" required />
    <x-forms.text-input name="password" label="Password" type="password" autocomplete="new-password" required />
    <x-forms.text-input name="password_confirmation" label="Confirm Password" type="password" autocomplete="new-password" required />

    <x-ui.button type="submit" severity="primary" style="width:100%;justify-content:center;margin-top:var(--sp-lg);">Create account</x-ui.button>
</form>

<p style="text-align:center;margin-top:var(--sp-xl);font-size:var(--text-sm);color:var(--text-muted);">
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
</p>
@endsection
