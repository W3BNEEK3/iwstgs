<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class AddCacVariantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'complexity_level'      => ['required', 'in:low,mid,high'],
            'scenario_text'         => ['required', 'string'],
            'scaffolding_text_low'  => ['nullable', 'string'],
            'scaffolding_text_mid'  => ['nullable', 'string'],
            'scaffolding_text_high' => ['nullable', 'string'],
            'context_text_low'      => ['nullable', 'string'],
            'context_text_mid'      => ['nullable', 'string'],
            'context_text_high'     => ['nullable', 'string'],
        ];
    }
}
