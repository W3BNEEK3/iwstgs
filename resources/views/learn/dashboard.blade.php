@extends('layouts.app')
@section('title', 'Learn')
@section('content')
<div style="max-width:800px;margin:80px auto;padding:0 1rem;">
    <h1>Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}. Your simulation dashboard will be built in Phase 5.</p>
</div>
@endsection
