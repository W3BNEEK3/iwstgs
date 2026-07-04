<tr id="prompt-{{ $prompt->id() }}">
    <td>{{ $prompt->toPrimitives()['trigger_dimension'] }}</td>
    <td>{{ $prompt->toPrimitives()['delivery_mode'] }}</td>
    <td>{{ $prompt->toPrimitives()['autonomy_level_filter'] ?? '— any —' }}</td>
    <td>{{ Str::limit($prompt->toPrimitives()['prompt_text'], 80) }}</td>
    <td>
        <button
            hx-delete="{{ route('admin.tasks.children.remove', [$taskId, 'prompts', $prompt->id()]) }}"
            hx-target="#prompt-{{ $prompt->id() }}"
            hx-swap="outerHTML">
            Remove
        </button>
    </td>
</tr>
