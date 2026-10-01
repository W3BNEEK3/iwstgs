@extends('admin.layouts.admin')

@section('title', 'Guide — Tips')

@section('content')
@include('admin.guide._tabs')

<p style="font-size:var(--text-sm);color:var(--text-muted);margin:0 0 var(--sp-lg);max-width:760px;">
    General advice on using Areyna well. At a quiet moment Tiroco picks the first active tip that suits the page and that the learner
    hasn't seen in 60 days, and may rephrase it to fit (the meaning must stay the same). Leave "Pages" empty for "anywhere".
</p>

@php($pageOptions = $pages)

<details class="panel" style="margin-bottom:var(--sp-xl);" @if ($errors->any()) open @endif>
    <summary style="cursor:pointer;font-weight:600;">Add a tip</summary>
    <form method="POST" action="{{ route('admin.guide.tips.store') }}" style="margin-top:var(--sp-lg);">
        @csrf
        @include('admin.guide._tip-fields', ['tip' => null])
        <x-ui.button type="submit" severity="primary" icon="add">Add tip</x-ui.button>
    </form>
</details>

<div style="display:flex;flex-direction:column;gap:var(--sp-md);">
    @foreach ($tips as $tip)
        <details class="panel">
            <summary style="cursor:pointer;display:flex;gap:var(--sp-sm);align-items:baseline;flex-wrap:wrap;">
                <x-ui.badge :tone="$tip->isActive ? 'success' : 'neutral'">{{ $tip->isActive ? 'Active' : 'Off' }}</x-ui.badge>
                <strong>{{ $tip->area }}</strong>
                <span style="color:var(--text-muted);font-size:var(--text-sm);">{{ $tip->text }}</span>
            </summary>
            <form method="POST" action="{{ route('admin.guide.tips.update', $tip->id) }}" style="margin-top:var(--sp-lg);">
                @csrf
                @method('PUT')
                @include('admin.guide._tip-fields', ['tip' => $tip])
                <div class="btn-row">
                    <x-ui.button type="submit" severity="primary" icon="save">Save</x-ui.button>
                    <x-ui.button type="submit" severity="destructive" icon="delete" form="delete-tip-{{ $tip->id }}">Delete</x-ui.button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.guide.tips.destroy', $tip->id) }}" id="delete-tip-{{ $tip->id }}" onsubmit="return confirm('Delete this tip?')">
                @csrf
                @method('DELETE')
            </form>
        </details>
    @endforeach
</div>
@endsection
