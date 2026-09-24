{{-- Mobile-card counterpart to _row.blade.php's <tr> — table.css hides .table-frame
     below 640px with nothing to replace it unless this exists too. Included both on
     initial page load and (with $oob=true) as an out-of-band fragment inside _row's
     toggle response, so a click from either the desktop row or this card keeps both
     representations in sync regardless of which one is currently visible. --}}
@php $oob = $oob ?? false; @endphp
<div class="mobile-row-card" id="flag-card-{{ $flag->flagKey }}" @if ($oob) data-swap-oob @endif style="cursor:default;">
    <div class="mobile-row-top">
        <span>
            <span class="mobile-row-title"><code>{{ $flag->flagKey }}</code></span>
            <span class="mobile-row-sub">{{ $flag->module }}</span>
        </span>
    </div>
    <x-ui.button
        :severity="$flag->isEnabled ? 'primary' : 'secondary'"
        x-data
        @click="$ajax('{{ route('admin.feature-flags.toggle', $flag->flagKey) }}', { method: 'PATCH', target: '#flag-row-{{ $flag->flagKey }}' })"
        style="margin-top:var(--sp-sm);"
    >
        {{ $flag->isEnabled ? 'Enabled' : 'Disabled' }}
    </x-ui.button>
</div>
