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
                    <tr id="flag-row-{{ $flag->flagKey }}">
                        <td><code>{{ $flag->flagKey }}</code></td>
                        <td><x-ui.badge tone="neutral">{{ $flag->module }}</x-ui.badge></td>
                        <td>
                            <x-ui.button
                                :severity="$flag->isEnabled ? 'primary' : 'secondary'"
                                hx-patch="{{ route('admin.feature-flags.toggle', $flag->flagKey) }}"
                                hx-target="#flag-row-{{ $flag->flagKey }}"
                                hx-swap="outerHTML"
                            >
                                {{ $flag->isEnabled ? 'Enabled' : 'Disabled' }}
                            </x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-row-cards">
        @foreach ($flags as $flag)
            @include('admin.feature-flags._card', ['flag' => $flag])
        @endforeach
    </div>
@endif
@endsection
