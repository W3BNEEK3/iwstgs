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

class ShiftBoardScenario2Seeder extends Seeder
{
    public function run(): void
    {
        $project = ProjectTemplateModel::where('title', 'ShiftBoard — Staff Scheduling for Retail Teams')->firstOrFail();
        $rubricSet = $project->rubricSet;

        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => 2],
            [
                'title'                  => 'Sprint 2 — Concurrency & Validation',
                'narrative_context'      => 'A manager reported that two staff members somehow claimed the same open shift at the exact same time. The resulting schedule conflict caused major issues on the floor.',
                'situation_trigger'      => "Slack message from Product Manager: \"We had a double-booking issue yesterday. Two people clicked 'Claim Shift' at the same time and both were approved. We need to prevent this immediately.\"",
                'situation_trigger_type' => 'slack_message',
                'learner_role_label'     => 'Backend Engineer',
                'default_autonomy_level' => 'mid',
                'is_diagnostic'          => false,
                'is_published'           => true,
                'is_active'              => true,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'SB-22: Prevent shift double-booking'],
            [
                'material_type'    => 'ticket',
                'content'          => "As a manager, I need shifts to be claimed by exactly one person so that we don't have overlapping coverage.\n\nAcceptance Criteria:\n- If two users try to claim the same shift simultaneously, only the first succeeds.\n- The second user receives a clear validation error.",
                'embedded_signals' => ['race condition', 'database locking or atomic updates'],
                'display_order'    => 1,
            ],
        );

        $task = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 1],
            [
                'title'      => 'Fix the shift double-booking race condition',
                'task_brief' => "Implement a solution to prevent two users from claiming the same open shift at the same time. Write a brief explanation of how your solution handles concurrency.",
                'domain'     => 'backend', 'task_type' => 'core',
                'role_tags'  => ['backend'], 'tools' => ['php', 'laravel', 'mysql'],
                'prerequisite_concepts' => ['database transactions', 'locking', 'race conditions'],
                'is_cac_runtime_set'    => true,
                'fixed_complexity'      => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural'      => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'],
                'model_response_summary' => 'Uses database transactions with pessimistic locking (e.g. lockForUpdate) or optimistic locking to prevent the race condition.',
                'time_limit_minutes'    => 60, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $task->id, 'type' => 'code'],
            ['label' => 'Concurrency fix', 'description' => 'The code implementing the lock or atomic update.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $task->id, 'type' => 'written_explanation'],
            ['label' => 'Concurrency explanation', 'description' => 'Explain how your fix works under load.', 'is_required' => true, 'display_order' => 2],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $task->id, 'complexity_level' => $level],
                [
                    'scenario_text'        => match($level) {
                        'low'  => "Fix the double-booking issue. Use a database transaction and a pessimistic lock (like `lockForUpdate()`) to ensure only one process can claim the shift.",
                        'mid'  => "Fix the double-booking issue caused by a race condition. Explain the approach you chose to ensure atomic updates.",
                        'high' => "Fix the double-booking issue. Design a concurrency-safe solution, implement it, and discuss the trade-offs of your chosen locking strategy.",
                    },
                    'scaffolding_text_low' => "Consider: what happens if two requests read the shift status before either one updates it?",
                    'scaffolding_text_mid' => "Think about how you can use the database to serialize access to the shift record.",
                    'scaffolding_text_high'=> "No scaffolding.",
                    'context_text_low'     => "Full context provided.",
                    'context_text_mid'     => "Partial context provided.",
                    'context_text_high'    => "Sparse context.",
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $task->id, 'task_dimension_label' => 'Concurrency handling'],
            [
                'parent_dimension_id'    => 'dim_implementation',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'The solution effectively prevents race conditions using database transactions and appropriate locking (pessimistic or optimistic).',
                'weight'                 => '1.000', 'dimension_weight' => '0.500',
                'claude_detection_hint'  => 'Look for `lockForUpdate()`, `sharedLock()`, or atomic `update` statements with WHERE conditions checking the previous state.',
                'distinguished_description' => 'Uses transactions and locking correctly, and explicitly explains why the chosen locking strategy is appropriate.',
                'proficient_description'    => 'Uses transactions and locking correctly.',
                'developing_description'    => 'Attempts to fix the issue but the lock might be placed incorrectly or a transaction is missing.',
                'beginning_description'     => 'Fails to address the race condition at the database level.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Prevent shift double-booking'],
            [
                'description'       => 'Fix the race condition allowing multiple users to claim the same shift.',
                'default_priority'  => 'must_have', 'role_tags' => ['backend'],
                'task_id'           => $task->id, 'dependency_item_ids' => null, 'display_order' => 1,
            ],
        );
    }
}
