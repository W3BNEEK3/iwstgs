@extends('layouts.app')
@section('title', 'System Administration — IWSTGS')
@section('content')
<div class="auth-container">
    <div class="auth-card">

        <div class="auth-header">
            <div style="width: 36px; height: 36px; background: #111; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h2 class="auth-title">System Administration</h2>
            <p class="auth-subtitle" style="font-size: 0.8rem; color: #888;">Restricted access. Authorised personnel only.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sys.login.store') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="off" required autofocus value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="off" required>
            </div>

            <div class="form-group" style="margin-top: 2rem;">
                <button type="submit" class="btn">Access System</button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.75rem; color: #bbb;">
            Organisation admin? <a href="{{ route('login') }}">Sign in here</a>
        </div>

    </div>
</div>
@endsection
