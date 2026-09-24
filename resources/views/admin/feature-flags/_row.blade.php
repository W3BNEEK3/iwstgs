<tr id="flag-row-{{ $flag->flagKey }}">
    <td><code>{{ $flag->flagKey }}</code></td>
    <td><x-ui.badge tone="neutral">{{ $flag->module }}</x-ui.badge></td>
    <td>
        <x-ui.button
            :severity="$flag->isEnabled ? 'primary' : 'secondary'"
            x-data
            @click="$ajax('{{ route('admin.feature-flags.toggle', $flag->flagKey) }}', { method: 'PATCH', target: '#flag-row-{{ $flag->flagKey }}' })"
        >
            {{ $flag->isEnabled ? 'Enabled' : 'Disabled' }}
        </x-ui.button>
    </td>
</tr>
@include('admin.feature-flags._card', ['flag' => $flag, 'oob' => true])
