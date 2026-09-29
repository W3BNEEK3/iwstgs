@extends('admin.layouts.admin')

@section('title', 'Guide — Resources')

@section('content')
@include('admin.guide._tabs')

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:var(--sp-lg);flex-wrap:wrap;margin-bottom:var(--sp-lg);">
    <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;max-width:680px;">
        Outside learning resources Tiroco may recommend when a skill keeps scoring below proficient. Tiroco only ever suggests
        resources from this list, one at a time, never the same one twice, and never one whose link check failed.
        Links are checked weekly (<code>php artisan guide:check-resources</code>).
    </p>
    <div class="btn-row">
        <form method="POST" action="{{ route('admin.guide.resources.check') }}">
            @csrf
            <x-ui.button type="submit" severity="secondary" icon="link">Check links now</x-ui.button>
        </form>
        <x-ui.button :href="route('admin.guide.resources.create')" icon="add">Add resource</x-ui.button>
    </div>
</div>

<div class="table-frame">
    <table class="data-table">
        <thead><tr><th>Resource</th><th>Helps with</th><th>Kind</th><th>Level</th><th>Link</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @foreach ($resources as $r)
                <tr>
                    <td style="max-width:360px;">
                        <strong>{{ $r->name }}</strong> @unless ($r->isFree) <x-ui.badge tone="warning">Paid</x-ui.badge> @endunless
                        <div style="font-size:var(--text-sm);color:var(--text-muted);">{{ $r->blurb }}</div>
                    </td>
                    <td style="font-size:var(--text-sm);">{{ implode(', ', array_map(fn ($d) => $dimensions[$d] ?? $d, $r->dimensions)) }}</td>
                    <td>{{ $r->kind }}</td>
                    <td>{{ $r->level }}</td>
                    <td>
                        @if ($r->lastCheckOk === false)
                            <x-ui.badge tone="error">Broken</x-ui.badge>
                        @elseif ($r->lastCheckOk === true)
                            <x-ui.badge tone="success">OK</x-ui.badge>
                        @else
                            <x-ui.badge tone="neutral">Not checked</x-ui.badge>
                        @endif
                        <a href="{{ $r->url }}" target="_blank" rel="noopener noreferrer" style="font-size:var(--text-xs);display:block;">Open</a>
                    </td>
                    <td><x-ui.badge :tone="$r->isActive ? 'success' : 'neutral'">{{ $r->isActive ? 'Active' : 'Off' }}</x-ui.badge></td>
                    <td><x-ui.button :href="route('admin.guide.resources.edit', $r->id)" severity="secondary" icon="edit">Edit</x-ui.button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
