<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; } // route middleware already enforces role

    public function rules(): array
    {
        return [
            'title'                 => ['required', 'string', 'max:300'],
            'project_type'          => ['required', 'string', 'max:100'],
            'business_context'      => ['required', 'string'],
            'specialization_tags'   => ['required', 'array', 'min:1'],
            'specialization_tags.*' => ['string'],
            'difficulty_level'      => ['required', 'in:beginner,intermediate,advanced'],
            'tagline'               => ['nullable', 'string', 'max:500'],
            'business_domain'       => ['nullable', 'string', 'max:200'],
        ];
    }
}
