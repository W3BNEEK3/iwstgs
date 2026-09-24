@extends('layouts.shells.dashboard')

@php
    $guideContext = ['diagnosticComplete' => true];
@endphp

@section('title', 'Diagnostic Complete — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Diagnostic Complete</h1>
    <p style="color:var(--text-muted);margin:0;">Your starting rank has been assigned.</p>
</div>

<x-forms.form-section title="Your Starting Rank">
    <div style="display:flex;align-items:center;gap:var(--sp-md);">
        <x-ui.badge tone="info">
            <x-ui.icon name="military_tech" :size="14" /> {{ $board->assignedRankTier }}-{{ $board->assignedRankLevel }}
        </x-ui.badge>
    </div>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin-top:var(--sp-md);">
        This is where you'll start — rank can move up or down over time based on how your work goes.
    </p>
</x-forms.form-section>

<div style="margin-top:var(--sp-xl);">
    <x-ui.button :href="route('learn.catalogue')" severity="primary" icon="arrow_forward">Browse Projects</x-ui.button>
</div>
@endsection
