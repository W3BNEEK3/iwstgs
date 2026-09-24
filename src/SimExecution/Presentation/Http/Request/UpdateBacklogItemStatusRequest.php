<?php
namespace Src\SimExecution\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBacklogItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_status' => ['required', 'in:in_sprint,in_progress,done,blocked'],
        ];
    }
}
