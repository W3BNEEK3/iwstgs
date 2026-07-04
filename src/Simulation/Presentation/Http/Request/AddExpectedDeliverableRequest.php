<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class AddExpectedDeliverableRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'          => ['required', 'in:written_explanation,artifact,code,diagram,document'],
            'label'         => ['required', 'string', 'max:300'],
            'description'   => ['nullable', 'string'],
            'is_required'   => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
