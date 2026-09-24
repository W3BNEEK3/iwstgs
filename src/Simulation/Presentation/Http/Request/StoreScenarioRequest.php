<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreScenarioRequest extends FormRequest
{
    public function authorize(): bool { return true; } // route middleware already enforces role

    public function rules(): array
    {
        return [
            'title'                   => ['required', 'string', 'max:300'],
            'narrative_context'       => ['required', 'string'],
            'situation_trigger'       => ['required', 'string'],
            'situation_trigger_type'  => ['required', 'in:slack_message,email,meeting_summary,incident_report,ticket,handover_note'],
            'default_autonomy_level'  => ['required', 'in:low,mid,high'],
            'learner_role_label'      => ['nullable', 'string', 'max:200'],
            'is_diagnostic'           => ['nullable', 'boolean'],
        ];
    }
}
