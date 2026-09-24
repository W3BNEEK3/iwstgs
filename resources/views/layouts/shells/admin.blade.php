{{-- Same structural shell as dashboard.blade.php, admin nav items — this is the
     unification point the design doc calls out (§8.4): one parameterized shell,
     not a hand-rolled second implementation. Scenarios/Tasks/Criteria/Vault are
     nested under a project/scenario/task id, so they don't get top-level sidebar
     entries — same reasoning the original admin nav already followed. --}}
@extends('layouts.app')

@php
    $navItems = [
        ['route' => 'admin.projects.index', 'label' => 'Projects', 'icon' => 'inventory_2', 'active' => 'admin.projects.*'],
        ['route' => 'admin.competence-dimensions.index', 'label' => 'Competency Model', 'icon' => 'category', 'active' => 'admin.competence-dimensions.*'],
        ['route' => 'admin.human-review.index', 'label' => 'Human Review', 'icon' => 'fact_check', 'active' => 'admin.human-review.*'],
        ['route' => 'admin.ai-engine.index', 'label' => 'AI Engine', 'icon' => 'smart_toy', 'active' => 'admin.ai-engine.*'],
        ['route' => 'admin.feature-flags.index', 'label' => 'Feature Flags', 'icon' => 'toggle_on', 'active' => 'admin.feature-flags.*'],
    ];
@endphp

@section('page')
<div class="app-shell" data-app-shell>
    @include('partials.navigation.sidebar', ['items' => $navItems, 'homeRoute' => 'admin.projects.index'])

    <div class="app-content-column">
        @include('partials.navigation.header')
        <main class="app-main">
            @yield('body')
        </main>
    </div>
</div>

@include('partials.navigation.mobile-drawer', ['items' => $navItems, 'homeRoute' => 'admin.projects.index'])
@endsection
