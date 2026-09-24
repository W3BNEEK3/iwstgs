<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'                  => ['required', 'string', 'max:300'],
            'task_brief'             => ['required', 'string'],
            'consequence_task_ids'   => ['nullable', 'string'],
            'suggestion_task_ids'    => ['nullable', 'string'],
        ];
    }

    /** Parses the newline/comma-separated UUID textarea into a clean array. */
    public function parsedTaskIds(string $field): array
    {
        $raw = (string) $this->input($field, '');

        return array_values(array_unique(array_filter(array_map(
            'trim',
            preg_split('/[\n,]+/', $raw) ?: []
        ))));
    }
}
