<?php

namespace Src\Identity\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:200'],
            'email'    => ['required', 'email:rfc,dns', 'max:320'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}