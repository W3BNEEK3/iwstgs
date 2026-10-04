<?php
namespace Src\SimExecution\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class EnrolInProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Build projects choose a stack instead of a role (the role is implied).
            'role_id'          => ['required_without:stack_variant_id', 'nullable', 'uuid'],
            'stack_variant_id' => ['required_without:role_id', 'nullable', 'uuid'],
        ];
    }
}
