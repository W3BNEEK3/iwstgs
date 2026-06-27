@extends('layouts.app')
@section('title', 'Log In')
@section('content')
<div style="max-width:480px;margin:80px auto;padding:0 1rem;">
    <h1>Log in</h1>

    @if (session('success'))
        <p style="color:green">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label>
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
        </div>
        <button type="submit">Log In</button>
    </form>

    <p>No account? <a href="{{ route('register') }}">Create one</a></p>
</div>
@endsection
