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
