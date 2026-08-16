@extends('admin.layouts.admin')

@section('title', 'Feature Flags')

@section('content')
<h1 style="margin-bottom:var(--sp-2xl);">Feature Flags</h1>

@if (empty($flags))
    <div class="empty-state">
        <x-ui.icon name="toggle_off" :size="32" />
        <p>No feature flags found.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Flag</th><th>Module</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($flags as $flag)
                    @include('admin.feature-flags._row', ['flag' => $flag])
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
