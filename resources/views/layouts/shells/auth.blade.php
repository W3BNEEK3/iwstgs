{{-- Login, register, "become a learner" — centered, no sidebar, no card wrapper
     around the form itself (design doc §8.2, §11) --}}
@extends('layouts.app')

@section('page')
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:var(--sp-2xl) var(--sp-lg);">
    <div style="width:100%;max-width:400px;">
        <div style="display:flex;align-items:center;gap:var(--sp-sm);justify-content:center;margin-bottom:var(--sp-2xl);">
            <span class="brand-mark">AR</span>
            <span class="brand-name" style="font-size:var(--text-lg);">{{ config('app.name', 'Areyna') }}</span>
        </div>

        @if (session('success'))
            <div class="badge badge-success" style="display:flex;width:100%;box-sizing:border-box;padding:var(--sp-sm) var(--sp-md);margin-bottom:var(--sp-lg);border-radius:var(--radius-sm);">
                <span class="material-symbols-outlined" style="font-size:16px;margin-right:6px;">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if (session('info'))
            <div class="badge badge-info" style="display:flex;width:100%;box-sizing:border-box;padding:var(--sp-sm) var(--sp-md);margin-bottom:var(--sp-lg);border-radius:var(--radius-sm);">
                <span class="material-symbols-outlined" style="font-size:16px;margin-right:6px;">info</span>
                {{ session('info') }}
            </div>
        @endif
        {{-- Field-level validation errors render inline via <x-forms.text-input>,
             not as a summary block here (design doc §10.4). --}}

        @yield('body')
    </div>
</div>
<x-guide :context="$guideContext ?? []" />
@endsection
