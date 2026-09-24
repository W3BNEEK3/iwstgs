<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioReferenceMaterialModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskExpectedDeliverableModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskCacVariantModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskGuidancePromptModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ArtifactVaultItemModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\BacklogItemTemplateModel;

class MedQueueSeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate throughout this seeder — a bare create() duplicated the
        // entire project the first time this was re-run after Phase 9 content was
        // added below, since nothing here was keyed for a safe second run.
        $project = ProjectTemplateModel::updateOrCreate(
            ['title' => 'MedQueue — Hospital Patient Queue System'],
            [
                'tagline'             => 'Build the backend for a clinic patient-flow platform.',
                'project_type'        => 'web_application',
                'business_domain'     => 'healthcare',
                'business_context'    => 'A regional clinic needs to replace paper-based patient queuing...',
                'stakeholders'        => [
                    ['name' => 'Adaeze Obi', 'role' => 'Clinic Manager', 'priority' => 'high', 'concern' => 'throughput'],
                    ['name' => 'Grace Adeyemi', 'role' => 'Head Nurse', 'priority' => 'medium', 'concern' => 'fairness'],
                ],
                'overarching_constraints' => [
                    ['type' => 'regulatory', 'description' => 'HIPAA-style patient data privacy must be respected throughout.'],
                    ['type' => 'technical', 'description' => 'The clinic\'s internet connection is unreliable — the system should tolerate brief offline periods.'],
                ],
                'tech_context'        => ['existing_stack' => ['PHP', 'Laravel', 'MySQL'], 'team_size' => 6, 'deployment_environment' => 'on-premise clinic server', 'special_requirements' => null],
                'specialization_tags' => ['backend', 'api'],
                'coding_guidelines'   => 'PSR-12; thin controllers.',
                'velocity_estimate'   => ['sprint_days' => 10],
                'difficulty_level'    => 'intermediate',
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
                'title' => 'MedQueue PRD',
                'content' => 'Product requirements: queue creation, priority triage, wait-time display...',
                'rank_gate' => null, 'phase_gate' => 'pre_induction', 'is_reference_doc' => true, 'display_order' => 1,
            ],
        );

        // Ungated (rank_gate null) so it serves both the Junior-1/2 fully-pre-written case
        // and the Junior-3/Mid-1 fill-in-the-blanks case (Integration Spec §15) — the
        // difference between those two is whether the sprint-planning form renders it
        // read-only or editable, not the content itself.
        ArtifactVaultItemModel::updateOrCreate(
            ['project_id' => $project->id, 'document_type' => 'sprint_goal_template'],
            [
                'title' => 'Sprint 1 Goal',
                'content' => "This sprint, deliver the queue API's core functionality. Complete the 'Must Have' backlog items — starting with the enqueue endpoint — before picking up anything lower priority. Make sure your implementation validates input correctly before considering the sprint done.",
                'rank_gate' => null, 'phase_gate' => 'post_induction', 'is_reference_doc' => false, 'display_order' => 2,
            ],
        );

        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => 1],
            [
                'title' => 'Sprint 1 — The Queue API',
                'narrative_context' => 'You have joined the MedQueue team mid-sprint...',
                'situation_trigger' => "Slack from the Tech Lead: \"Can you take the queue-enqueue endpoint? Ticket MQ-14.\"",
                'situation_trigger_type' => 'slack_message',
                'learner_role_label' => 'Backend Engineer', 'default_autonomy_level' => 'mid',
                'is_diagnostic' => false, 'is_published' => true, 'is_active' => true,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'MQ-14: Enqueue patient'],
            [
                'material_type' => 'ticket',
                'content' => "As a nurse, I want to add a patient to the queue so that they are seen in order...",
                'embedded_signals' => ['priority triage is out of scope for MQ-14'], 'display_order' => 1,
            ],
        );

        $task = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 1],
            [
                'title' => 'Implement the enqueue endpoint',
                'task_brief' => 'Add POST /queue/enqueue that validates and stores a patient in the queue.',
                'domain' => 'backend', 'task_type' => 'core',
                'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['http', 'validation'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'A validated controller + form request + queue insert.',
                'time_limit_minutes' => 90, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $task->id, 'type' => 'code'],
            ['label' => 'Endpoint code', 'description' => 'Controller + validation + persistence.', 'is_required' => true, 'display_order' => 1],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $task->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Enqueue variant at {$level} complexity.",
                    'scaffolding_text_low' => 'Step-by-step guidance.', 'scaffolding_text_mid' => 'Hints only.', 'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
                ],
            );
        }

        TaskGuidancePromptModel::updateOrCreate(
            ['task_id' => $task->id, 'trigger_dimension' => 'dim_problem_analysis'],
            [
                'prompt_text' => 'Have you considered what happens when the queue is full?',
                'autonomy_level_filter' => 'low', 'delivery_mode' => 'proactive', 'display_order' => 1,
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $task->id, 'task_dimension_label' => 'Endpoint correctness'],
            [
                'parent_dimension_id' => 'dim_implementation',
                'complexity_level' => 'mid', 'criterion_text' => 'The endpoint validates input and persists the patient correctly.',
                'weight' => '0.400', 'dimension_weight' => '0.300',
                'claude_detection_hint' => 'Look for a form request / validation and a DB insert.',
                'distinguished_description' => 'Robust validation, clear errors, idempotent insert.',
                'proficient_description'    => 'Validates required fields and inserts correctly.',
                'developing_description'    => 'Inserts but with weak/partial validation.',
                'beginning_description'     => 'No validation or incorrect persistence.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'MedQueue PRD §2',
            ],
        );

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $task->id, 'task_dimension_label' => 'Problem framing'],
            [
                'parent_dimension_id' => 'dim_problem_analysis',
                'complexity_level' => 'mid', 'criterion_text' => 'The learner identifies edge cases (full queue, duplicate patient).',
                'weight' => '0.300', 'dimension_weight' => '0.200',
                'claude_detection_hint' => 'Look for acknowledgment of edge cases in the text layer.',
                'distinguished_description' => 'Names multiple edge cases and handles them.',
                'proficient_description'    => 'Names the key edge cases.',
                'developing_description'    => 'Mentions one edge case.',
                'beginning_description'     => 'No edge-case awareness.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        // Sprint 1 product backlog — only the first item is linked to an authored Task
        // (task_id), same as a real backlog where tickets get written up ahead of tasks.
        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Implement the enqueue endpoint'],
            [
                'description' => 'POST /queue/enqueue — validate and store a patient in the queue (MQ-14).',
                'default_priority' => 'must_have', 'role_tags' => ['backend'],
                'task_id' => $task->id, 'dependency_item_ids' => null, 'display_order' => 1,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Add queue position lookup endpoint'],
            [
                'description' => 'GET /queue/{id}/position — return a patient\'s current position and estimated wait.',
                'default_priority' => 'must_have', 'role_tags' => ['backend'],
                'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 2,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Add priority triage flag to queue entries'],
            [
                'description' => 'Let a nurse mark a patient as urgent so they move ahead of routine entries.',
                'default_priority' => 'should_have', 'role_tags' => ['backend'],
                'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 3,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Write API documentation for queue endpoints'],
            [
                'description' => 'Document request/response shapes for the enqueue and position-lookup endpoints.',
                'default_priority' => 'could_have', 'role_tags' => ['backend'],
                'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 4,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Add rate limiting to queue submissions'],
            [
                'description' => 'Prevent duplicate/rapid-fire enqueue requests from the same device.',
                'default_priority' => 'wont_have', 'role_tags' => ['backend'],
                'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 5,
            ],
        );

        // Phase 9 adaptive content — a real consequence + suggestion variant for
        // the enqueue task, so ConsequenceTaskInjector/SuggestionTaskInjector
        // have something to actually inject once their flags are on. Both target
        // dim_implementation, the same dimension "Endpoint correctness" scores,
        // so a later pass resolves the gap flag the failure raised.
        // updateOrCreate throughout — MedQueueSeeder above this point uses plain
        // create(), left as-is; new content added here must be re-run-safe.
        $consequenceTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 2],
            [
                'title' => 'Revisit: Implement the enqueue endpoint',
                'task_brief' => "Two separate reports came in this week. A nurse mentioned that a patient, Wale Adeyemi, showed up twice in this morning's queue — she wasn't sure which entry to call. Separately, IT flagged that for about twenty minutes yesterday afternoon the queue stopped accepting anyone new; front desk had to fall back to writing names on paper until it started working again. Investigate both and fix whatever's causing them.",
                'domain' => 'backend', 'task_type' => 'consequence',
                'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['http', 'validation'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'consequence_task_ids' => [], 'suggestion_task_ids' => [],
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'A validated controller + form request + queue insert, with each edge case (duplicate patient, full queue) explicitly handled rather than incidentally correct.',
                'time_limit_minutes' => 90, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $consequenceTask->id, 'type' => 'code'],
            ['label' => 'Endpoint code', 'description' => 'Controller + validation + persistence, with edge cases explicitly handled.', 'is_required' => true, 'display_order' => 1],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $consequenceTask->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Revisit the enqueue endpoint at {$level} complexity.",
                    'scaffolding_text_low' => 'List each edge case before writing any code.', 'scaffolding_text_mid' => 'List each edge case before writing any code.', 'scaffolding_text_high' => 'List each edge case before writing any code.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Full context.', 'context_text_high' => 'Full context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $consequenceTask->id, 'task_dimension_label' => 'Endpoint correctness (revisit)'],
            [
                'parent_dimension_id' => 'dim_implementation', 'complexity_level' => 'mid',
                'criterion_text' => 'The endpoint explicitly validates input, rejects duplicate patients, and rejects submissions once the queue is full.',
                'weight' => '0.700', 'dimension_weight' => '0.500',
                'claude_detection_hint' => 'Look for explicit checks for all three cases (missing/invalid fields, duplicate patient, full queue), not just a happy-path insert. Partial coverage of only one case is at most developing.',
                'distinguished_description' => 'All three cases handled with clear, distinct error responses and evident reasoning about why each matters.',
                'proficient_description'    => 'All three cases handled correctly.',
                'developing_description'    => 'One or two cases handled; at least one missing.',
                'beginning_description'     => 'Only the happy path is handled.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'MedQueue PRD §2',
            ],
        );

        $suggestionTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 3],
            [
                'title' => 'Development Focus: Input Validation Patterns',
                'task_brief' => "This isn't tied to a specific bug — it's worth deliberately practicing a general pattern: writing validation as an explicit, testable set of rules up front (a form request or equivalent), rather than a series of ad-hoc if-checks discovered while debugging. Take any small endpoint (the enqueue endpoint is fine to reuse) and describe how you'd structure its validation rules so each rule is independently readable and testable.",
                'domain' => 'backend', 'task_type' => 'suggestion',
                'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['validation'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'consequence_task_ids' => [], 'suggestion_task_ids' => [],
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => null, 'model_response_summary' => 'Describes validation as a declarative, independently-testable rule set rather than inline conditional checks, and explains why that structure holds up better as an endpoint grows.',
                'time_limit_minutes' => null, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $suggestionTask->id, 'type' => 'written_explanation'],
            ['label' => 'Validation structure', 'description' => 'How you would structure the validation rules and why.', 'is_required' => true, 'display_order' => 1],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $suggestionTask->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Input validation patterns, presented at {$level} complexity.",
                    'scaffolding_text_low' => 'Consider: what makes a validation rule "independently testable"?', 'scaffolding_text_mid' => 'Consider what makes a validation rule independently testable.', 'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Full context.', 'context_text_high' => 'Full context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $suggestionTask->id, 'task_dimension_label' => 'Validation structure'],
            [
                'parent_dimension_id' => 'dim_implementation', 'complexity_level' => 'mid',
                'criterion_text' => 'The learner describes validation as an explicit, declarative rule set rather than ad-hoc conditional checks, and explains the maintainability benefit.',
                'weight' => '1.000', 'dimension_weight' => '0.500',
                'claude_detection_hint' => 'Look for a structural description (e.g. a form request class, a rules array) and an explicit "why" — not just "I would validate the input."',
                'distinguished_description' => 'Names a concrete structural approach and explains why it scales better as rules grow.',
                'proficient_description'    => 'Names a concrete structural approach.',
                'developing_description'    => 'Mentions validation generally without a structural approach.',
                'beginning_description'     => 'No structural reasoning at all.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        $task->update([
            'consequence_task_ids' => [$consequenceTask->id],
            'suggestion_task_ids'  => [$suggestionTask->id],
        ]);

        // Two more real core tasks, filling in backlog items that previously had
        // a description but no authored Task behind them.
        $positionTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 4],
            [
                'title' => 'Add queue position lookup endpoint',
                'task_brief' => "Add GET /queue/{id}/position that returns a patient's current position in the queue and an estimated wait time. Position is 1-indexed among patients still waiting; estimated wait should be a simple function of position (e.g. position × average consult time), not a hardcoded value.",
                'domain' => 'backend', 'task_type' => 'core',
                'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['http', 'database queries'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'A GET endpoint that queries the still-waiting patients ordered by enqueue time, computes 1-indexed position, and derives an estimated wait from position rather than hardcoding it.',
                'time_limit_minutes' => 60, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $positionTask->id, 'type' => 'code'],
            ['label' => 'Endpoint code', 'description' => 'Position lookup + wait-time estimate.', 'is_required' => true, 'display_order' => 1],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $positionTask->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Queue position lookup at {$level} complexity.",
                    'scaffolding_text_low' => 'Consider: what does "position" mean once someone ahead of you has already been served?', 'scaffolding_text_mid' => 'Consider what happens to position once someone ahead has been served.', 'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $positionTask->id, 'task_dimension_label' => 'Position + wait-time correctness'],
            [
                'parent_dimension_id' => 'dim_implementation', 'complexity_level' => 'mid',
                'criterion_text' => 'Position is computed dynamically from still-waiting patients (not a stored counter that goes stale), and wait time is derived from position.',
                'weight' => '0.600', 'dimension_weight' => '0.400',
                'claude_detection_hint' => 'A stored/incrementing position field that never decreases when someone is served is a common wrong-but-plausible answer — watch for it. Correct submissions recompute position by counting still-waiting patients ahead in enqueue order.',
                'distinguished_description' => 'Dynamic position computed correctly, wait time clearly derived from it, and handles the empty-queue/position-1 edge case explicitly.',
                'proficient_description'    => 'Dynamic position computed correctly, wait time derived from it.',
                'developing_description'    => 'Position or wait-time logic present but incorrect (e.g. a stale stored counter).',
                'beginning_description'     => 'No real position/wait-time logic, or a hardcoded value.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        $triageTask = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 5],
            [
                'title' => 'Add priority triage flag to queue entries',
                'task_brief' => "Let a nurse mark a waiting patient as urgent, moving them ahead of routine entries without disturbing the relative order of everyone else. Explain how you decided where an urgent patient should land among other urgent patients already in the queue.",
                'domain' => 'backend', 'task_type' => 'core',
                'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['database queries', 'ordering/sorting logic'],
                'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural' => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'Urgent patients are ordered among themselves by enqueue time (not just dumped at the front in arbitrary order), and routine patients keep their existing relative order — the explanation names this reasoning explicitly.',
                'time_limit_minutes' => 60, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $triageTask->id, 'type' => 'written_explanation'],
            ['label' => 'Ordering reasoning', 'description' => 'How urgent patients are ordered relative to each other and to routine patients.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $triageTask->id, 'type' => 'code'],
            ['label' => 'Triage flag code', 'description' => 'The endpoint/logic that marks a patient urgent and re-orders the queue.', 'is_required' => true, 'display_order' => 2],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $triageTask->id, 'complexity_level' => $level],
                [
                    'scenario_text' => "Priority triage at {$level} complexity.",
                    'scaffolding_text_low' => 'Consider: if two patients are both urgent, who goes first?', 'scaffolding_text_mid' => 'Consider how two urgent patients should be ordered relative to each other.', 'scaffolding_text_high' => 'No scaffolding.',
                    'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $triageTask->id, 'task_dimension_label' => 'Ordering correctness'],
            [
                'parent_dimension_id' => 'dim_design', 'complexity_level' => 'mid',
                'criterion_text' => 'Urgent patients are ordered among themselves by enqueue time, and routine patients retain their existing relative order — this is explicitly reasoned about, not incidental.',
                'weight' => '0.600', 'dimension_weight' => '0.400',
                'claude_detection_hint' => 'Look for explicit reasoning about ties between two urgent patients (e.g. "urgent patients are still ordered by when they arrived"), not just "urgent patients go first."',
                'distinguished_description' => 'Explicit, correct reasoning about ordering among urgent patients and preservation of routine ordering.',
                'proficient_description'    => 'Correct ordering behavior with some reasoning given.',
                'developing_description'    => 'Urgent patients move ahead but ordering among themselves is unaddressed or arbitrary.',
                'beginning_description'     => 'No real ordering logic beyond a boolean flag with no query-level effect.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        BacklogItemTemplateModel::where('project_id', $project->id)
            ->where('title', 'Add queue position lookup endpoint')
            ->update(['task_id' => $positionTask->id]);

        BacklogItemTemplateModel::where('project_id', $project->id)
            ->where('title', 'Add priority triage flag to queue entries')
            ->update(['task_id' => $triageTask->id]);
    }
}
