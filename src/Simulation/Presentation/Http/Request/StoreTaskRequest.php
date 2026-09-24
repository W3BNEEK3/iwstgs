<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; } // route middleware enforces role

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:300'],
            'task_brief'           => ['required', 'string'],
            'task_type'            => ['required', 'in:core,consequence,suggestion,diagnostic_scenario,diagnostic_consequence'],
            'domain'               => ['nullable', 'string', 'max:100'],
            'is_cac_runtime_set'   => ['required', 'boolean'],
            'is_architectural'     => ['required', 'boolean'],
            'planning_layer_active'=> ['required', 'boolean'],
            'role_tags'            => ['nullable', 'array'],
            'role_tags.*'          => ['string'],
            'tools'                => ['nullable', 'array'],
            'tools.*'              => ['string'],
            'model_response_summary' => ['nullable', 'string'],
            'time_limit_minutes'   => ['nullable', 'integer', 'min:1'],
            // fixed_* are required only when is_cac_runtime_set is false
            'fixed_complexity'     => ['nullable', 'in:low,mid,high', 'required_if:is_cac_runtime_set,false'],
            'fixed_autonomy'       => ['nullable', 'in:low,mid,high', 'required_if:is_cac_runtime_set,false'],
            'fixed_context_fidelity'=> ['nullable', 'in:low,mid,high', 'required_if:is_cac_runtime_set,false'],
        ];
    }
}
