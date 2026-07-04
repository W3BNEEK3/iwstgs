<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricCriterionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'criterion_text'   => ['required', 'string'],
            'weight'           => ['required', 'numeric', 'between:0,1'],
            'dimension_weight' => ['required', 'numeric', 'between:0,1'],
        ];
    }
}
