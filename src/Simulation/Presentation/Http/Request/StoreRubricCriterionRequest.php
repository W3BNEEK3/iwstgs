<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreRubricCriterionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'task_dimension_label'      => ['required', 'string', 'max:200'],
            'parent_dimension_id'       => ['required', 'string', 'max:20', 'exists:competence_dimensions,id'],
            'complexity_level'          => ['required', 'in:low,mid,high'],
            'criterion_text'            => ['required', 'string'],
            'weight'                    => ['required', 'numeric', 'between:0,1'],
            'dimension_weight'          => ['required', 'numeric', 'between:0,1'],
            'claude_detection_hint'     => ['required', 'string'],
            'distinguished_description' => ['required', 'string'],
            'proficient_description'    => ['required', 'string'],
            'developing_description'    => ['required', 'string'],
            'beginning_description'     => ['required', 'string'],
            'is_architectural'          => ['boolean'],
            'is_planning_layer'         => ['boolean'],
            'reference_doc_anchor'      => ['nullable', 'string'],
        ];
    }
}
