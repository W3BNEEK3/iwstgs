@extends('layouts.shells.dashboard')

@section('title', 'Role Qualification — ' . config('app.name', 'Areyna'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Role Qualification</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Which roles your current competency graph qualifies you for, and what's missing where it doesn't.</p>
</div>

<div style="display:flex;flex-direction:column;gap:var(--sp-lg);">
    @foreach ($results as $result)
        <x-forms.form-section :title="$result->roleTitle">
            <div style="margin-bottom:var(--sp-md);">
                @if ($result->isQualified)
                    <x-ui.badge tone="success"><x-ui.icon name="check_circle" :size="14" /> Qualified</x-ui.badge>
                @else
                    <x-ui.badge tone="error"><x-ui.icon name="cancel" :size="14" /> Not yet qualified</x-ui.badge>
                @endif
            </div>

            @if (!empty($result->met))
                <div style="margin-bottom:var(--sp-sm);">
                    <div style="font-size:var(--text-sm);font-weight:600;margin-bottom:4px;">Dimensions you clear</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach ($result->met as $req)
                            <x-ui.badge tone="success">{{ $req->dimensionId }}: {{ ucfirst($req->achievedTier) }} (needs {{ ucfirst($req->requiredTier) }})</x-ui.badge>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!empty($result->unmet))
                <div>
                    <div style="font-size:var(--text-sm);font-weight:600;margin-bottom:4px;">Dimensions still needed</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach ($result->unmet as $req)
                            <x-ui.badge tone="warning">{{ $req->dimensionId }}: {{ ucfirst($req->achievedTier) }} (needs {{ ucfirst($req->requiredTier) }})</x-ui.badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-forms.form-section>
    @endforeach
</div>

<div style="margin-top:var(--sp-xl);">
    <x-ui.button :href="route('learn.profile')" severity="secondary" icon="arrow_back">Back to Profile</x-ui.button>
</div>
@endsection
