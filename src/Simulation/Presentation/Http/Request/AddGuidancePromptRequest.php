<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class AddGuidancePromptRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'trigger_dimension'     => ['required', 'string', 'max:200'],
            'prompt_text'           => ['required', 'string'],
            'delivery_mode'         => ['required', 'in:proactive,reactive'],
            'autonomy_level_filter' => ['nullable', 'in:low,mid,high'],
            'display_order'         => ['nullable', 'integer', 'min:0'],
        ];
    }
}
