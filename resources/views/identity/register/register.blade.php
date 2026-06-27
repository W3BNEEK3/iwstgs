@extends('layouts.app')
@section('title', 'Create Account')
@section('content')
<div style="max-width:480px;margin:80px auto;padding:0 1rem;">
    <h1>Create your account</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
