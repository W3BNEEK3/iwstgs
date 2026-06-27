<?php

namespace Src\SimExecution\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class EnrolAsLearnerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_category'   => ['required', 'in:inexperienced,experienced'],
            'years_experience' => [
                'nullable',
                'required_if:entry_category,experienced',
                'integer',
                'min:1',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'entry_category.in'               => 'Please select either inexperienced or experienced.',
            'years_experience.required_if'    => 'Please enter your years of experience.',
        ];
    }
}