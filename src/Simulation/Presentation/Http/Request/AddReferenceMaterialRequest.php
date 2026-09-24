<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class AddReferenceMaterialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'material_type'  => ['required', 'in:document,email,slack_message,ticket,report,notes'],
            'material_title' => ['required', 'string', 'max:300'],
            'content'        => ['required', 'string'],
            'display_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
