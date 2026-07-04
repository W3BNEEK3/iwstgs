<?php

namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreVaultItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'in:business_context,prd,srs,sad,coding_guidelines,glossary,sprint_goal_template'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'rank_gate' => ['nullable', 'string', 'max:20'],
            'phase_gate' => ['nullable', 'string', 'in:pre_induction,post_induction,post_sprint_1,mid_session,advanced_only'],
            'is_reference_doc' => ['boolean'],
            'display_order' => ['required', 'integer', 'min:1'],
        ];
    }
}
