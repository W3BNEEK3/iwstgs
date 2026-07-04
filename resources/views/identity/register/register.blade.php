@extends('layouts.app')
@section('title', 'Sign Up - IWSTGS')
@section('content')
<div class="auth-container">
    <div class="auth-card">
        
        <div class="auth-header">
            <h2 class="auth-title">Create an account</h2>
            <p class="auth-subtitle">Join us to start learning and managing your tasks.</p>
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

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
                <label for="name">Full Name</label>
                <input id="name" name="name" type="text" required autofocus value="{{ old('name') }}">
            </div>
            
            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>

            <div class="form-group" style="margin-top: 2rem;">
                <button type="submit" class="btn">Create account</button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>

    </div>
</div>
@endsection
