<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class AddKnowledgeAnchorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'concept_name'             => ['required', 'string', 'max:300'],
            'is_required'              => ['required', 'boolean'],
            'concept_id'               => ['nullable', 'string', 'uuid'],
            'domain'                   => ['nullable', 'string', 'max:200'],
            'application_expectation'  => ['nullable', 'string'],
            'remediation_hint'         => ['nullable', 'string'],
        ];
    }
}
