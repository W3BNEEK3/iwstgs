@extends('layouts.shells.dashboard')

@section('title', 'Settings — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Settings</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Account details and appearance.</p>
</div>

<x-forms.form-section title="Account">
    <div style="display:flex;flex-direction:column;gap:var(--sp-md);">
        <div>
            <div style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:2px;">Name</div>
            <div>{{ $user->name }}</div>
        </div>
        <div>
            <div style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:2px;">Email</div>
            <div>{{ $user->email }}</div>
        </div>
    </div>
</x-forms.form-section>

<div style="margin-top:var(--sp-xl);"></div>
<x-forms.form-section title="Appearance">
    <div style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:var(--sp-sm);">Theme</div>
    <div class="theme-switch">
        <button type="button" data-theme-choice="light">Light</button>
        <button type="button" data-theme-choice="dark">Dark</button>
        <button type="button" data-theme-choice="system">System</button>
    </div>
</x-forms.form-section>
@endsection
