<?php
namespace Src\Submission\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class SubmitTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'layer1_text'    => ['nullable', 'string', 'max:20000'],
            'layer3_code'    => ['nullable', 'string', 'max:50000'],
            'artifacts'      => ['array'],
            'artifacts.*'    => ['array'],
            'artifacts.*.*'  => ['file', 'max:10240', 'mimes:pdf,doc,docx,png,jpg,jpeg,txt,md'],
        ];
    }
}
