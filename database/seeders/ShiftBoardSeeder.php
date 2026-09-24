<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\RoleDefinitionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ArtifactVaultItemModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\BacklogItemTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioReferenceMaterialModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskCacVariantModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskExpectedDeliverableModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

/**
 * A second, beginner-difficulty umbrella project alongside MedQueue's
 * intermediate one — 0-years-experience roles across two specializations
 * (backend, frontend) so learners can pick a role, not just enrol into the
 * project's only option. updateOrCreate throughout, matching the fix already
 * applied to MedQueueSeeder.
 */
class ShiftBoardSeeder extends Seeder
{
    public function run(): void
    {
        $backendRole = RoleDefinitionModel::updateOrCreate(
            ['title' => 'Associate Backend Engineer'],
            [
                'specialization_tags'  => ['backend'],
                'min_years_experience' => 0,
                'dimension_weights'    => [
                    'dim_problem_analysis' => 0.15, 'dim_design' => 0.15, 'dim_implementation' => 0.35,
                    'dim_testing' => 0.15, 'dim_debugging' => 0.15, 'dim_communication' => 0.05,
                ],
                'dimension_thresholds' => [
                    'dim_problem_analysis' => 'developing', 'dim_design' => 'developing',
                    'dim_implementation' => 'developing', 'dim_testing' => 'developing',
                    'dim_debugging' => 'developing', 'dim_communication' => 'developing',
                ],
                'is_lead_role' => false,
            ],
        );

        $frontendRole = RoleDefinitionModel::updateOrCreate(
            ['title' => 'Associate Frontend Engineer'],
            [
                'specialization_tags'  => ['frontend'],
                'min_years_experience' => 0,
                'dimension_weights'    => [
                    'dim_problem_analysis' => 0.15, 'dim_design' => 0.20, 'dim_implementation' => 0.30,
                    'dim_testing' => 0.15, 'dim_debugging' => 0.10, 'dim_communication' => 0.10,
                ],
                'dimension_thresholds' => [
                    'dim_problem_analysis' => 'developing', 'dim_design' => 'developing',
                    'dim_implementation' => 'developing', 'dim_testing' => 'developing',
                    'dim_debugging' => 'developing', 'dim_communication' => 'developing',
                ],
                'is_lead_role' => false,
            ],
        );

        $project = ProjectTemplateModel::updateOrCreate(
            ['title' => 'ShiftBoard — Staff Scheduling for Retail Teams'],
            [
                'tagline'             => 'Build a shift-request tool for a small retail chain.',
                'project_type'        => 'web_application',
                'business_domain'     => 'retail_operations',
                'business_context'    => 'A five-store retail chain currently manages shift swaps and time-off requests over group text. Managers want a simple internal tool: staff submit requests, managers approve or reject them, and the schedule reflects it.',
                'stakeholders'        => [
                    ['name' => 'Marcus Webb', 'role' => 'Store Manager', 'priority' => 'high', 'concern' => 'coverage'],
                    ['name' => 'Priya Shah', 'role' => 'Retail Associate', 'priority' => 'medium', 'concern' => 'fairness and speed'],
                ],
                'overarching_constraints' => [
                    ['type' => 'technical', 'description' => 'No native mobile app in this phase — the tool must work well in a phone browser.'],
                    ['type' => 'technical', 'description' => 'Store managers are not technical — the tool must be self-explanatory, no training required.'],
                ],
                'tech_context'        => ['existing_stack' => ['PHP', 'Laravel', 'MySQL'], 'team_size' => 3, 'deployment_environment' => 'cloud', 'special_requirements' => null],
                'specialization_tags' => ['backend', 'frontend'],
                'coding_guidelines'   => 'PSR-12; keep controllers thin; prefer explicit validation over implicit conventions.',
                'velocity_estimate'   => ['sprint_days' => 8],
                'difficulty_level'    => 'beginner',
                'is_published'        => true,
                'is_active'           => true,
            ],
        );

        if ($project->rubric_set_id === null) {
            $rubricSet = RubricSetModel::create(['project_id' => $project->id, 'version' => '1.0']);
            $project->update(['rubric_set_id' => $rubricSet->id]);
        } else {
            $rubricSet = RubricSetModel::find($project->rubric_set_id);
        }

        ArtifactVaultItemModel::updateOrCreate(
            ['project_id' => $project->id, 'document_type' => 'prd'],
            [
                'title' => 'ShiftBoard PRD',
                'content' => 'Product requirements: staff submit a shift-swap or time-off request; a manager approves or rejects it; approved requests update the visible schedule. No payroll integration in this phase.',
                'rank_gate' => null, 'phase_gate' => 'pre_induction', 'is_reference_doc' => true, 'display_order' => 1,
            ],
        );

        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => 1],
            [
                'title' => 'Sprint 1 — Shift Requests',
                'narrative_context' => "You've just joined the ShiftBoard team as it starts building the core request flow. Nothing exists yet beyond a database schema sketch — this sprint is about getting a working request-and-approval loop in front of one pilot store.",
                'situation_trigger' => "Email from the Store Manager: \"Can we get something basic working for my store first? Staff need to be able to ask for a shift swap or a day off, and I need to see requests come in and approve or reject them. Doesn't need to be fancy yet.\"",
                'situation_trigger_type' => 'email',
                'learner_role_label' => 'Engineer', 'default_autonomy_level' => 'low',
                'is_diagnostic' => false, 'is_published' => true, 'is_active' => true,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'Pilot store request volume'],
            [
                'material_type' => 'notes',
                'content' => "Notes from a call with the pilot store manager: roughly 15 staff, expect 5-10 requests a week, mostly time-off rather than swaps. She wants to see requests sorted with the oldest pending one first.",
                'embedded_signals' => ['pending requests should be sortable/visible by submission order'], 'display_order' => 1,
            ],
        );

        // Backend: submit + approve.
        $submitTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 1],
            [
                'title' => 'Implement the shift-request submission endpoint',
                'task_brief' => 'Add POST /shift-requests that lets a staff member submit a time-off or shift-swap request: type (time_off or swap), the date(s) affected, and an optional note. Store it with a pending status.',
                'domain' => 'backend', 'task_type' => 'core',
                'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['http', 'validation'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'A validated endpoint that requires request type and date(s), rejects a missing/invalid type, and persists the request as pending.',
                'time_limit_minutes' => 60, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $submitTask->id, 'type' => 'code'],
            ['label' => 'Endpoint code', 'description' => 'Controller + validation + persistence.', 'is_required' => true, 'display_order' => 1],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $submitTask->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Shift-request submission at {$level} complexity.",
                    'scaffolding_text_low' => 'Start with: what fields does a request absolutely need before it can be reviewed?', 'scaffolding_text_mid' => 'Consider what fields a request needs before it can be reviewed.', 'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $submitTask->id, 'task_dimension_label' => 'Request validation and persistence'],
            [
                'parent_dimension_id' => 'dim_implementation', 'complexity_level' => 'low',
                'criterion_text' => 'The endpoint validates request type and date(s), and persists the request with a pending status.',
                'weight' => '1.000', 'dimension_weight' => '0.500',
                'claude_detection_hint' => 'Look for explicit validation of the type field (not just "any string accepted") and a stored status of pending on creation.',
                'distinguished_description' => 'Validates type against an explicit allowed set, validates date(s) are present and sensible, sets pending status, clear error messages.',
                'proficient_description'    => 'Validates type and date(s), sets pending status.',
                'developing_description'    => 'Persists a request but validation is weak or status handling is missing.',
                'beginning_description'     => 'No real validation; accepts and stores arbitrary input.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'ShiftBoard PRD',
            ],
        );

        $approveTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 2],
            [
                'title' => 'Implement the manager approval endpoint',
                'task_brief' => "Add an endpoint that lets a manager approve or reject a pending request. Once a request is approved or rejected, it should not be approvable/rejectable again. Explain what you'd return to the caller if someone tries to act on an already-decided request.",
                'domain' => 'backend', 'task_type' => 'core',
                'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['http', 'state machines'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'Approving/rejecting only works from pending status; a second attempt on an already-decided request is rejected with a clear response, not silently overwritten.',
                'time_limit_minutes' => 60, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $approveTask->id, 'type' => 'written_explanation'],
            ['label' => 'Re-decision handling', 'description' => 'What happens if an already-decided request is acted on again, and why.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $approveTask->id, 'type' => 'code'],
            ['label' => 'Endpoint code', 'description' => 'The approve/reject endpoint.', 'is_required' => true, 'display_order' => 2],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $approveTask->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Manager approval at {$level} complexity.",
                    'scaffolding_text_low' => 'Consider: what should happen if someone clicks "Approve" twice?', 'scaffolding_text_mid' => 'Consider what happens on a duplicate approval attempt.', 'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $approveTask->id, 'task_dimension_label' => 'State transition correctness'],
            [
                'parent_dimension_id' => 'dim_implementation', 'complexity_level' => 'low',
                'criterion_text' => 'A request can only transition from pending; acting on an already-decided request is explicitly rejected, not silently allowed.',
                'weight' => '1.000', 'dimension_weight' => '0.500',
                'claude_detection_hint' => 'Look for an explicit status check before mutating (e.g. "if status !== pending, reject") rather than an unconditional update.',
                'distinguished_description' => 'Explicit guard against re-deciding, a clear rejection response, and the reasoning is stated (idempotency/audit-trail concern).',
                'proficient_description'    => 'Explicit guard against re-deciding with a clear rejection response.',
                'developing_description'    => 'Some guard exists but behavior on a repeat action is unclear or inconsistent.',
                'beginning_description'     => 'No guard — a second action silently overwrites the first decision.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        // Frontend: the request form.
        $formTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 3],
            [
                'title' => 'Build the shift-request form',
                'task_brief' => "Build the staff-facing form for submitting a shift-request: a type selector (time off / swap), date picker, optional note, and a submit action. Explain how the form communicates validation errors back to the user — the manager described staff as not very tech-comfortable, so this matters.",
                'domain' => 'frontend', 'task_type' => 'core',
                'role_tags' => ['frontend'], 'tools' => ['html', 'css', 'javascript'], 'prerequisite_concepts' => ['forms', 'client-side validation'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => null, 'model_response_summary' => 'A form with a constrained type selector (not free text), inline field-level error messages placed next to the relevant field, and reasoning tied explicitly to the stated audience (non-technical staff).',
                'time_limit_minutes' => 60, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $formTask->id, 'type' => 'written_explanation'],
            ['label' => 'Error-handling approach', 'description' => 'How and where validation errors are shown to the user, and why.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $formTask->id, 'type' => 'code'],
            ['label' => 'Form markup/code', 'description' => 'The form itself.', 'is_required' => true, 'display_order' => 2],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $formTask->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Shift-request form at {$level} complexity.",
                    'scaffolding_text_low' => 'Consider: where does a person\'s eye go after clicking submit on a broken form?', 'scaffolding_text_mid' => 'Consider where a user looks after a failed submit.', 'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $formTask->id, 'task_dimension_label' => 'Usability for a non-technical audience'],
            [
                'parent_dimension_id' => 'dim_design', 'complexity_level' => 'low',
                'criterion_text' => 'Validation errors are shown inline, next to the relevant field, in plain language — and the choice is explicitly justified against the stated non-technical audience.',
                'weight' => '1.000', 'dimension_weight' => '0.500',
                'claude_detection_hint' => 'Look for the reasoning to explicitly reference the audience constraint (non-technical staff), not just generic best-practice language. A generic "I would add error messages" with no audience reasoning is at most developing.',
                'distinguished_description' => 'Inline, plain-language errors with reasoning explicitly tied to the non-technical audience constraint.',
                'proficient_description'    => 'Inline, plain-language errors, some reasoning given.',
                'developing_description'    => 'Errors shown but generic (e.g. a single top-of-page alert) with no audience reasoning.',
                'beginning_description'     => 'No meaningful error handling described.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Implement the shift-request submission endpoint'],
            [
                'description' => 'POST /shift-requests — staff submit a time-off or swap request.',
                'default_priority' => 'must_have', 'role_tags' => ['backend'],
                'task_id' => $submitTask->id, 'dependency_item_ids' => null, 'display_order' => 1,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Implement the manager approval endpoint'],
            [
                'description' => 'Manager approves/rejects a pending request; already-decided requests are protected from re-decision.',
                'default_priority' => 'must_have', 'role_tags' => ['backend'],
                'task_id' => $approveTask->id, 'dependency_item_ids' => null, 'display_order' => 2,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Build the shift-request form'],
            [
                'description' => 'Staff-facing form to submit a shift-request, with inline validation feedback.',
                'default_priority' => 'must_have', 'role_tags' => ['frontend'],
                'task_id' => $formTask->id, 'dependency_item_ids' => null, 'display_order' => 3,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Show the pending-requests list to managers'],
            [
                'description' => 'A view for managers listing pending requests, oldest first, with approve/reject actions.',
                'default_priority' => 'should_have', 'role_tags' => ['frontend'],
                'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 4,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Notify staff when their request is decided'],
            [
                'description' => 'Email the requester when their request is approved or rejected.',
                'default_priority' => 'could_have', 'role_tags' => ['backend'],
                'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 5,
            ],
        );
    }
}
