@extends('layouts.app')
@section('title', 'Log In - IWSTGS')
@section('content')
<div class="auth-container">
    <div class="auth-card">
        
        <div class="auth-header">
            <h2 class="auth-title">Log In</h2>
            <p class="auth-subtitle">Welcome back. Please enter your details.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required autofocus value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                <div class="checkbox-group">
                    <input id="remember" name="remember" type="checkbox" value="1">
                    <label for="remember" style="margin-bottom: 0;">Remember me</label>
                </div>
                <a href="#" style="font-size: 0.875rem;">Forgot password?</a>
            </div>

            <div class="form-group" style="margin-top: 2rem;">
                <button type="submit" class="btn">Sign in</button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
            Don't have an account? <a href="{{ route('register') }}">Sign up</a>
        </div>

    </div>
</div>
@endsection
