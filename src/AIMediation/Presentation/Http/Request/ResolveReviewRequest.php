<?php
namespace Src\AIMediation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class ResolveReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:proficient,not_proficient,escalate'],
            'notes'    => ['nullable', 'string', 'max:5000'],
        ];
    }
}
