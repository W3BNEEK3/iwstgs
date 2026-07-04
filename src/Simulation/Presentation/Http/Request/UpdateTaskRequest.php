<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:300'],
            'task_brief' => ['required', 'string'],
        ];
    }
}
