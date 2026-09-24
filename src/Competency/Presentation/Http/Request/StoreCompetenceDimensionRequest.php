<?php
namespace Src\Competency\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompetenceDimensionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:120'],
            'short_label'           => ['required', 'string', 'max:60'],
            'core_question'         => ['required', 'string', 'max:300'],
            'observable_indicators' => ['required', 'string'],
        ];
    }
}
