<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioReferenceMaterialModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskExpectedDeliverableModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskCacVariantModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskGuidancePromptModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\BacklogItemTemplateModel;

/**
 * MedQueue Scenario 3 — Security & Observability.
 *
 * Content Rationale: The clinic has received a compliance audit finding:
 * patient data is accessible without proper authorization, and there is no
 * audit trail of queue actions. This scenario targets dim_design,
 * dim_implementation, and dim_communication (data-protection reasoning) — areas not
 * heavily covered in Scenarios 1 & 2. It gives the adaptive engine a
 * third scenario to route learners to when they show weaknesses in
 * security or professional/ethical reasoning.
 *
 * CAC Differentiation:
 *   low  — "The auth mechanism is given; implement the guard."
 *   mid  — "Choose an auth mechanism and implement it."
 *   high — "Design and implement the full access-control model, including the audit log."
 */
class MedQueueScenario3Seeder extends Seeder
{
    public function run(): void
    {
        $project  = ProjectTemplateModel::where('title', 'MedQueue — Hospital Patient Queue System')->firstOrFail();
        $rubricSet = $project->rubricSet;

        // ── Scenario ──────────────────────────────────────────────────────────
        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => 3],
            [
                'title'                  => 'Sprint 3 — Security & Audit Compliance',
                'narrative_context'      => 'The clinic has received a compliance audit report. Two findings require immediate action: (1) any staff member can view any patient\'s queue entry — there is no role check; (2) there is no record of who approved or modified queue entries, making it impossible to trace errors. The Data Protection Officer has given you two weeks.',
                'situation_trigger'      => "Email from Data Protection Officer: \"Attached is the audit summary. Findings F1 and F2 are classified as HIGH risk and must be resolved this sprint. F1: missing access control on patient records. F2: no audit log for queue state changes. Please confirm your approach before you start implementing.\"",
                'situation_trigger_type' => 'email',
                'learner_role_label'     => 'Backend Engineer',
                'default_autonomy_level' => 'mid',
                'is_diagnostic'          => false,
                'is_published'           => true,
                'is_active'              => true,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'Audit report — Findings F1 and F2'],
            [
                'material_type'    => 'document',
                'content'          => "F1 — Unauthorized data access (HIGH)\nAny authenticated user can call GET /queue/{id} and retrieve full patient details including name and registration number, regardless of their role. Role-based access control is absent.\n\nF2 — Missing audit trail (HIGH)\nThere is no record of which staff member approved a triage escalation or marked a patient as served. The clinic cannot reconstruct the sequence of actions on a patient record in the event of a complaint.",
                'embedded_signals' => ['role-based, not just auth-based', 'immutable audit log — not an updatable flag', 'approved by DPO = professional responsibility in scope'],
                'display_order'    => 1,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'Existing roles in the system'],
            [
                'material_type'    => 'notes',
                'content'          => "Users have one of three roles: nurse (can enqueue, view own ward's patients, mark served), manager (can approve triage escalations, view all patients), admin (full access). Role is stored on the users table.",
                'embedded_signals' => ['role is on users table', 'nurse scope is ward-limited — not all patients'],
                'display_order'    => 2,
            ],
        );

        // ── Task 1: Implement role-based access control ────────────────────────
        $rbacTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 1],
            [
                'title'      => 'Implement role-based access control on queue endpoints',
                'task_brief' => "The audit finding says any authenticated user can view any patient's queue entry regardless of role. Fix this. Your implementation must:\n\n• Nurses can view and enqueue patients in their own ward only.\n• Managers can view all patients and approve/reject triage escalations.\n• Admins have full access.\n\nExplain how you structured the authorization logic and where you drew the line — specifically, what happens if a nurse tries to access a patient from another ward.",
                'domain'     => 'backend', 'task_type' => 'core',
                'role_tags'  => ['backend'], 'tools' => ['php', 'laravel'],
                'prerequisite_concepts' => ['authorization', 'policies', 'role-based access control', 'middleware'],
                'is_cac_runtime_set'    => true,
                'fixed_complexity'      => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural'      => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'],
                'model_response_summary' => 'Uses a Policy or Gate with explicit role checks. Nurses are scoped to their ward (not a blanket nurse-can-see-anything approach). A 403 — not a 404 — is returned for a ward mismatch, and the reasoning is stated.',
                'time_limit_minutes'    => 90, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $rbacTask->id, 'type' => 'code'],
            ['label' => 'Authorization code', 'description' => 'Policy, Gate, or middleware implementing the role rules.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $rbacTask->id, 'type' => 'written_explanation'],
            ['label' => 'Authorization approach', 'description' => 'How you structured authorization and what happens on a ward mismatch.', 'is_required' => true, 'display_order' => 2],
        );

        TaskCacVariantModel::updateOrCreate(
            ['task_id' => $rbacTask->id, 'complexity_level' => 'low'],
            [
                'scenario_text'        => "The access control rules are defined in the audit report and the system note. Your job: implement them using Laravel Policies. Focus on the nurse ward-scope rule — that's the most nuanced one.",
                'scaffolding_text_low' => "In a Laravel Policy, how do you check both that the user is a nurse AND that the patient is in their ward?",
                'scaffolding_text_mid' => "Think about the ward scope: a nurse should only see their own ward, not all patients.",
                'scaffolding_text_high'=> "No scaffolding.",
                'context_text_low'     => "Full audit report and system role note provided.",
                'context_text_mid'     => "Audit report and system role note provided.",
                'context_text_high'    => "Audit report provided.",
            ],
        );

        TaskCacVariantModel::updateOrCreate(
            ['task_id' => $rbacTask->id, 'complexity_level' => 'mid'],
            [
                'scenario_text'        => "The audit report and role structure are provided. Choose how to structure the authorization (Policy, Gate, middleware) and implement it so nurses are ward-scoped and managers have broader access. Explain your structural choice.",
                'scaffolding_text_low' => "Consider: should you use a Policy per model or a single middleware? What are the trade-offs?",
                'scaffolding_text_mid' => "Think about which authorization approach keeps the rules readable as they grow.",
                'scaffolding_text_high'=> "No scaffolding.",
                'context_text_low'     => "Full audit report and role note provided.",
                'context_text_mid'     => "Audit report and role note provided.",
                'context_text_high'    => "Audit report provided.",
            ],
        );

        TaskCacVariantModel::updateOrCreate(
            ['task_id' => $rbacTask->id, 'complexity_level' => 'high'],
            [
                'scenario_text'        => "Using the audit report and role definitions, design and implement the full access-control model for the queue system. Address both the nurse ward-scope requirement and the manager triage-approval scope. Describe the authorization structure you chose, why you chose it over alternatives, and what response code a nurse receives when accessing a patient outside their ward (and why that matters for security).",
                'scaffolding_text_low' => "Start with the nurse role — it has the most nuanced scoping rule.",
                'scaffolding_text_mid' => "Consider: what HTTP status code signals 'you are authenticated but not authorized', and why does that matter here?",
                'scaffolding_text_high'=> "No scaffolding.",
                'context_text_low'     => "Full audit report and role definitions provided.",
                'context_text_mid'     => "Audit report and role note provided.",
                'context_text_high'    => "Audit report provided.",
            ],
        );

        TaskGuidancePromptModel::updateOrCreate(
            ['task_id' => $rbacTask->id, 'trigger_dimension' => 'dim_communication'],
            [
                'prompt_text'           => "The audit report describes this as HIGH risk. What does that classification imply about how quickly and carefully this needs to be addressed?",
                'autonomy_level_filter' => 'low', 'delivery_mode' => 'proactive', 'display_order' => 1,
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $rbacTask->id, 'task_dimension_label' => 'Authorization correctness'],
            [
                'parent_dimension_id'    => 'dim_implementation',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'Nurses are ward-scoped (not a blanket nurse-can-see-all), managers have broader access, and a ward mismatch returns 403 — not 404 — with the reasoning stated.',
                'weight'                 => '0.500', 'dimension_weight' => '0.400',
                'claude_detection_hint'  => 'A 404 on a ward mismatch is a common security error (it leaks that the resource exists). Look for explicit 403 and a justification. A "nurse sees all patients" implementation is beginning regardless of how tidy the code is.',
                'distinguished_description' => 'Ward scoped, 403 on mismatch with reason given, and the authorization approach scales cleanly (policy/gate not ad-hoc if checks in the controller).',
                'proficient_description'    => 'Ward scoped, 403 on mismatch.',
                'developing_description'    => 'Some role check exists but is incomplete (e.g. nurse can see all, no ward scope).',
                'beginning_description'     => 'No meaningful authorization beyond login check.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'Audit report F1',
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $rbacTask->id, 'task_dimension_label' => 'Professional responsibility (data protection)'],
            [
                'parent_dimension_id'    => 'dim_communication',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'The learner acknowledges the patient-data sensitivity and applies the principle of least privilege — nurses can only access what they need for their role, not more.',
                'weight'                 => '0.500', 'dimension_weight' => '0.300',
                'claude_detection_hint'  => 'Look for language that frames the requirement as protecting patient privacy, not just satisfying a ticket. A response that passes the technical test without acknowledging data sensitivity is at most developing.',
                'distinguished_description' => 'Explicitly frames the ward-scope rule as a patient-privacy requirement (least privilege), not just a functional constraint.',
                'proficient_description'    => 'Acknowledges data sensitivity in the explanation.',
                'developing_description'    => 'Implements the rule but treats it as a purely functional requirement with no mention of why it matters.',
                'beginning_description'     => 'No awareness of the data-protection dimension.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'Audit report F1',
            ],
        );

        // ── Task 2: Implement the audit log ────────────────────────────────────
        $auditTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 2],
            [
                'title'      => 'Implement an immutable audit log for queue state changes',
                'task_brief' => "The audit finding requires a record of who performed each queue state change (enqueue, triage escalation, mark-served). Design and implement the audit log. Key constraints:\n\n• Entries must be immutable — once written, they cannot be updated or deleted.\n• Each entry must record: action type, patient ID, performing user, timestamp.\n• Explain how you enforce immutability and what you'd do if someone tried to delete an entry directly in the database.",
                'domain'     => 'backend', 'task_type' => 'core',
                'role_tags'  => ['backend'], 'tools' => ['php', 'laravel', 'mysql'],
                'prerequisite_concepts' => ['audit trails', 'immutability', 'database design', 'observability'],
                'is_cac_runtime_set'    => true,
                'fixed_complexity'      => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural'      => true, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'],
                'model_response_summary' => 'Creates an append-only queue_audit_log table (no update/delete permissions granted, or a DB trigger that prevents them). Each write is a new INSERT, never an UPDATE. The learner explains what "immutable" means in this context and acknowledges that application-level immutability is only as strong as DB-level access controls.',
                'time_limit_minutes'    => 75, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $auditTask->id, 'type' => 'written_explanation'],
            ['label' => 'Audit log design', 'description' => 'Schema design and immutability enforcement approach.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $auditTask->id, 'type' => 'code'],
            ['label' => 'Implementation', 'description' => 'Migration + write logic (model observer or service call).', 'is_required' => true, 'display_order' => 2],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $auditTask->id, 'complexity_level' => $level],
                [
                    'scenario_text'        => match($level) {
                        'low'  => "Create an audit log table with columns for: action_type, patient_id, performed_by (user_id), and created_at. Write a new row every time a patient is enqueued, triage-escalated, or marked served. The table should never be updated or deleted from.",
                        'mid'  => "Design and implement an audit log for queue state changes. The log must be immutable and cover enqueue, triage escalation, and mark-served actions. Explain how you enforce immutability and what 'immutable' really means at the application vs database level.",
                        'high' => "The DPO requires an audit log that can withstand a compliance review — meaning it must be demonstrably tamper-resistant, not just \"we don't call DELETE\". Design the audit log, implement it, and explain the gap between application-level and database-level immutability. What would you recommend to a client who needs a legally admissible audit trail?",
                    },
                    'scaffolding_text_low' => "Consider: if you use Model::update() on an audit log row, what does that break?",
                    'scaffolding_text_mid' => "Think about the difference between 'we don't call delete in the code' and 'it cannot be deleted'.",
                    'scaffolding_text_high'=> "No scaffolding.",
                    'context_text_low'     => "Full audit report and system context provided.",
                    'context_text_mid'     => "Audit report provided.",
                    'context_text_high'    => "Audit finding F2 only.",
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $auditTask->id, 'task_dimension_label' => 'Immutability design'],
            [
                'parent_dimension_id'    => 'dim_design',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'The audit log is append-only (INSERT never UPDATE/DELETE), and the learner explains the distinction between application-level and database-level immutability.',
                'weight'                 => '0.600', 'dimension_weight' => '0.400',
                'claude_detection_hint'  => 'An "immutable" log implemented only as "we don\'t call delete" (no DB-level protection) is developing. Distinguished names the DB-level gap explicitly and proposes at least one mechanism (restricted permissions, trigger, write-once storage) to address it.',
                'distinguished_description' => 'Append-only by design, explicitly distinguishes app-level vs DB-level immutability, and proposes a concrete mechanism for the DB level.',
                'proficient_description'    => 'Append-only with the distinction acknowledged.',
                'developing_description'    => 'Append-only by convention only (no acknowledgment of the DB-level gap).',
                'beginning_description'     => 'Uses updatable records or overwrites entries.',
                'is_architectural' => true, 'is_planning_layer' => true, 'reference_doc_anchor' => 'Audit report F2',
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Implement role-based access control on queue endpoints'],
            [
                'description'      => 'Address audit finding F1 — nurse ward-scope, manager/admin roles.',
                'default_priority' => 'must_have', 'role_tags' => ['backend'],
                'task_id'          => $rbacTask->id, 'dependency_item_ids' => null, 'display_order' => 8,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Implement immutable audit log for queue state changes'],
            [
                'description'      => 'Address audit finding F2 — append-only log of enqueue/triage/serve actions.',
                'default_priority' => 'must_have', 'role_tags' => ['backend'],
                'task_id'          => $auditTask->id, 'dependency_item_ids' => null, 'display_order' => 9,
            ],
        );
    }
}
