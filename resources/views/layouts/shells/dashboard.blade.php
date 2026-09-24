{{-- Learner-facing app: collapsible sidebar + bar-less header (design doc §8.3).
     Nav items are intentionally minimal — only routes that exist today. Add to
     the $navItems array below as later phases ship real routes; don't
     pre-build links to nothing. --}}
@extends('layouts.app')

@php
    $navItems = [
        ['route' => 'learn.catalogue', 'label' => 'Projects', 'icon' => 'inventory_2', 'active' => 'learn.catalogue*'],
    ];
    // Route registration doesn't imply access — FeatureMiddleware 404s a
    // disabled feature rather than 403ing, so a flagged-off link would be a
    // dead end. Check the flag directly, same "don't link to nothing" rule
    // this file's own header comment already states.
    if (app(\Src\Shared\Infrastructure\Feature\FeatureFlagService::class)->isEnabled('reporting.learner_profile')) {
        $navItems[] = ['route' => 'learn.profile', 'label' => 'Profile', 'icon' => 'account_circle', 'active' => 'learn.profile*'];
    }
    $navItems[] = ['route' => 'learn.settings', 'label' => 'Settings', 'icon' => 'settings', 'active' => 'learn.settings*'];
@endphp

@section('page')
<div class="app-shell" x-data :class="{ 'is-collapsed': $store.nav.collapsed }">
    @include('partials.navigation.sidebar', ['items' => $navItems, 'homeRoute' => 'learn.catalogue'])

    <div class="app-content-column">
        @include('partials.navigation.header')
        <main class="app-main">
            @yield('body')
        </main>
    </div>
</div>

@include('partials.navigation.mobile-drawer', ['items' => $navItems, 'homeRoute' => 'learn.catalogue'])

<x-guide :context="$guideContext ?? []" />
@endsection
