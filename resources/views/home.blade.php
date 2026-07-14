@extends('layouts.app')
@section('title', 'IWSTGS — Intelligent Workplace Simulation Training & Grading System')
@section('content')
<div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; font-family: 'Inter', system-ui, sans-serif; padding: 2rem;">

    <div style="max-width: 640px; width: 100%; text-align: center;">

        <div style="width: 48px; height: 48px; background: #000; border-radius: 10px; margin: 0 auto 2rem; display: flex; align-items: center; justify-content: center;">
            <span style="color: #fff; font-size: 1.25rem; font-weight: 700; letter-spacing: -0.05em;">IW</span>
        </div>

        <h1 style="font-size: 2rem; font-weight: 700; color: #111; margin: 0 0 1rem; letter-spacing: -0.03em;">
            IWSTGS
        </h1>
        <p style="font-size: 1.05rem; color: #555; margin: 0 0 2.5rem; line-height: 1.6;">
            Intelligent Workplace Simulation Training &amp; Grading System
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('login') }}"
               style="padding: 0.65rem 1.5rem; background: #000; color: #fff; border-radius: 6px; font-size: 0.9rem; font-weight: 500; text-decoration: none;">
                Organisation Login
            </a>
            <a href="{{ route('register') }}"
               style="padding: 0.65rem 1.5rem; background: #fff; color: #111; border: 1.5px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; font-weight: 500; text-decoration: none;">
                Create Account
            </a>
        </div>

        <p style="margin-top: 3rem; font-size: 0.78rem; color: #aaa;">
            &copy; {{ date('Y') }} IWSTGS. All rights reserved.
        </p>

    </div>

</div>
@endsection
