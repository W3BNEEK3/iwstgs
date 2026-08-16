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
        $project = ProjectTemplateModel::create([
            'title'               => 'MedQueue — Hospital Patient Queue System',
            'tagline'             => 'Build the backend for a clinic patient-flow platform.',
            'project_type'        => 'web_application',
            'business_domain'     => 'healthcare',
            'business_context'    => 'A regional clinic needs to replace paper-based patient queuing...',
            'stakeholders'        => [['role' => 'Clinic Manager', 'concern' => 'throughput'], ['role' => 'Nurse', 'concern' => 'fairness']],
            'overarching_constraints' => ['HIPAA-style data privacy', 'Offline-tolerant'],
            'tech_context'        => ['stack' => ['PHP', 'Laravel', 'MySQL']],
            'specialization_tags' => ['backend', 'api'],
            'coding_guidelines'   => 'PSR-12; thin controllers.',
            'velocity_estimate'   => ['sprint_days' => 10],
            'difficulty_level'    => 'intermediate',
            'is_published'        => true,
            'is_active'           => true,
        ]);

        $rubricSet = RubricSetModel::create(['project_id' => $project->id, 'version' => '1.0']);
        $project->update(['rubric_set_id' => $rubricSet->id]);

        ArtifactVaultItemModel::create([
            'project_id' => $project->id, 'document_type' => 'prd', 'title' => 'MedQueue PRD',
            'content' => 'Product requirements: queue creation, priority triage, wait-time display...',
            'rank_gate' => null, 'phase_gate' => 'pre_induction', 'is_reference_doc' => true, 'display_order' => 1,
        ]);

        $scenario = ScenarioTemplateModel::create([
            'project_id' => $project->id, 'sequence_order' => 1, 'title' => 'Sprint 1 — The Queue API',
            'narrative_context' => 'You have joined the MedQueue team mid-sprint...',
            'situation_trigger' => "Slack from the Tech Lead: \"Can you take the queue-enqueue endpoint? Ticket MQ-14.\"",
            'situation_trigger_type' => 'slack_message',
            'learner_role_label' => 'Backend Engineer', 'default_autonomy_level' => 'mid',
            'is_diagnostic' => false, 'is_published' => true, 'is_active' => true,
        ]);

        ScenarioReferenceMaterialModel::create([
            'scenario_id' => $scenario->id, 'material_type' => 'ticket', 'title' => 'MQ-14: Enqueue patient',
            'content' => "As a nurse, I want to add a patient to the queue so that they are seen in order...",
            'embedded_signals' => ['priority triage is out of scope for MQ-14'], 'display_order' => 1,
        ]);

        $task = TaskModel::create([
            'scenario_id' => $scenario->id, 'sequence_order' => 1, 'title' => 'Implement the enqueue endpoint',
            'task_brief' => 'Add POST /queue/enqueue that validates and stores a patient in the queue.',
            'domain' => 'backend', 'task_type' => 'core',
            'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['http', 'validation'],
            'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
            'consequence_task_ids' => [], 'suggestion_task_ids' => [],
            'is_architectural' => false, 'planning_layer_active' => true,
            'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'A validated controller + form request + queue insert.',
            'time_limit_minutes' => 90, 'is_published' => true, 'is_active' => true,
        ]);

        TaskExpectedDeliverableModel::create([
            'task_id' => $task->id, 'type' => 'code', 'label' => 'Endpoint code',
            'description' => 'Controller + validation + persistence.', 'is_required' => true, 'display_order' => 1,
        ]);

        foreach (['low', 'mid', 'high'] as $i => $level) {
            TaskCacVariantModel::create([
                'task_id' => $task->id, 'complexity_level' => $level,
                'scenario_text' => "Enqueue variant at {$level} complexity.",
                'scaffolding_text_low' => 'Step-by-step guidance.', 'scaffolding_text_mid' => 'Hints only.', 'scaffolding_text_high' => 'No scaffolding.',
                'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
            ]);
        }

        TaskGuidancePromptModel::create([
            'task_id' => $task->id, 'trigger_dimension' => 'dim_problem_analysis',
            'prompt_text' => 'Have you considered what happens when the queue is full?',
            'autonomy_level_filter' => 'low', 'delivery_mode' => 'proactive', 'display_order' => 1,
        ]);

        RubricCriterionModel::create([
            'rubric_set_id' => $rubricSet->id, 'task_id' => $task->id,
            'task_dimension_label' => 'Endpoint correctness', 'parent_dimension_id' => 'dim_implementation',
            'complexity_level' => 'mid', 'criterion_text' => 'The endpoint validates input and persists the patient correctly.',
            'weight' => '0.400', 'dimension_weight' => '0.300',
            'claude_detection_hint' => 'Look for a form request / validation and a DB insert.',
            'distinguished_description' => 'Robust validation, clear errors, idempotent insert.',
            'proficient_description'    => 'Validates required fields and inserts correctly.',
            'developing_description'    => 'Inserts but with weak/partial validation.',
            'beginning_description'     => 'No validation or incorrect persistence.',
            'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'MedQueue PRD §2',
        ]);

        RubricCriterionModel::create([
            'rubric_set_id' => $rubricSet->id, 'task_id' => $task->id,
            'task_dimension_label' => 'Problem framing', 'parent_dimension_id' => 'dim_problem_analysis',
            'complexity_level' => 'mid', 'criterion_text' => 'The learner identifies edge cases (full queue, duplicate patient).',
            'weight' => '0.300', 'dimension_weight' => '0.200',
            'claude_detection_hint' => 'Look for acknowledgment of edge cases in the text layer.',
            'distinguished_description' => 'Names multiple edge cases and handles them.',
            'proficient_description'    => 'Names the key edge cases.',
            'developing_description'    => 'Mentions one edge case.',
            'beginning_description'     => 'No edge-case awareness.',
            'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
        ]);

        // Sprint 1 product backlog — only the first item is linked to an authored Task
        // (task_id), same as a real backlog where tickets get written up ahead of tasks.
        BacklogItemTemplateModel::create([
            'project_id' => $project->id, 'title' => 'Implement the enqueue endpoint',
            'description' => 'POST /queue/enqueue — validate and store a patient in the queue (MQ-14).',
            'default_priority' => 'must_have', 'role_tags' => ['backend'],
            'task_id' => $task->id, 'dependency_item_ids' => null, 'display_order' => 1,
        ]);

        BacklogItemTemplateModel::create([
            'project_id' => $project->id, 'title' => 'Add queue position lookup endpoint',
            'description' => 'GET /queue/{id}/position — return a patient\'s current position and estimated wait.',
            'default_priority' => 'must_have', 'role_tags' => ['backend'],
            'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 2,
        ]);

        BacklogItemTemplateModel::create([
            'project_id' => $project->id, 'title' => 'Add priority triage flag to queue entries',
            'description' => 'Let a nurse mark a patient as urgent so they move ahead of routine entries.',
            'default_priority' => 'should_have', 'role_tags' => ['backend'],
            'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 3,
        ]);

        BacklogItemTemplateModel::create([
            'project_id' => $project->id, 'title' => 'Write API documentation for queue endpoints',
            'description' => 'Document request/response shapes for the enqueue and position-lookup endpoints.',
            'default_priority' => 'could_have', 'role_tags' => ['backend'],
            'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 4,
        ]);

        BacklogItemTemplateModel::create([
            'project_id' => $project->id, 'title' => 'Add rate limiting to queue submissions',
            'description' => 'Prevent duplicate/rapid-fire enqueue requests from the same device.',
            'default_priority' => 'wont_have', 'role_tags' => ['backend'],
            'task_id' => null, 'dependency_item_ids' => null, 'display_order' => 5,
        ]);
    }
}
