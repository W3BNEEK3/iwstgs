<?php
namespace Src\SimExecution\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBacklogItemPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'in:must_have,should_have,could_have,wont_have'],
        ];
    }
}
