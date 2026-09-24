<tr id="prompt-{{ $prompt->id() }}">
    <td>{{ $prompt->toPrimitives()['trigger_dimension'] }}</td>
    <td>{{ ucfirst($prompt->toPrimitives()['delivery_mode']) }}</td>
    <td>{{ $prompt->toPrimitives()['autonomy_level_filter'] ? ucfirst($prompt->toPrimitives()['autonomy_level_filter']) : '— any —' }}</td>
    <td>{{ \Illuminate\Support\Str::limit($prompt->toPrimitives()['prompt_text'], 80) }}</td>
    <td>
        <x-ui.button severity="destructive" type="button" data-modal-open="remove-prompt-{{ $prompt->id() }}">Remove</x-ui.button>
        <x-overlays.confirm-modal
            :id="'remove-prompt-' . $prompt->id()"
            title="Remove this guidance prompt?"
            description="This can't be undone."
            :action="route('admin.tasks.children.remove', [$taskId, 'prompts', $prompt->id()])"
            :hx-target="'#prompt-' . $prompt->id()"
            confirmLabel="Remove"
        />
    </td>
</tr>
