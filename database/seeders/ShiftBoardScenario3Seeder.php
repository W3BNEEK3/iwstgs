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

class ShiftBoardScenario3Seeder extends Seeder
{
    public function run(): void
    {
        $project = ProjectTemplateModel::where('title', 'ShiftBoard — Staff Scheduling for Retail Teams')->firstOrFail();
        $rubricSet = $project->rubricSet;

        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => 3],
            [
                'title'                  => 'Sprint 3 — Overtime Rules & Validation',
                'narrative_context'      => 'The company is facing compliance issues regarding overtime pay. The system currently allows employees to claim shifts that put them over 40 hours for the week without any manager oversight.',
                'situation_trigger'      => "Email from HR: \"We need to implement strict overtime limits. An employee cannot claim a shift if it pushes their weekly total over 40 hours. If it does, the claim should be rejected and flagged for a manager to review.\"",
                'situation_trigger_type' => 'email',
                'learner_role_label'     => 'Backend Engineer',
                'default_autonomy_level' => 'mid',
                'is_diagnostic'          => false,
                'is_published'           => true,
                'is_active'              => true,
            ],
        );

        ScenarioReferenceMaterialModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'title' => 'SB-45: Enforce weekly overtime limits'],
            [
                'material_type'    => 'ticket',
                'content'          => "As HR, I need the system to prevent employees from exceeding 40 hours in a work week (Monday-Sunday).\n\nAcceptance Criteria:\n- When claiming a shift, calculate the user's total approved and pending hours for that week.\n- If adding the new shift exceeds 40 hours, reject the claim and return a specific error message.\n- Do NOT count rejected shift requests in the total.",
                'embedded_signals' => ['date math', 'complex validation rule'],
                'display_order'    => 1,
            ],
        );

        $task = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => 1],
            [
                'title'      => 'Implement overtime validation logic',
                'task_brief' => "Write the validation logic to prevent a user from claiming a shift if it puts them over 40 hours for the week. Explain how you calculate the total hours and ensure accuracy.",
                'domain'     => 'backend', 'task_type' => 'core',
                'role_tags'  => ['backend'], 'tools' => ['php', 'laravel'],
                'prerequisite_concepts' => ['validation rules', 'date/time calculation'],
                'is_cac_runtime_set'    => true,
                'fixed_complexity'      => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
                'is_architectural'      => false, 'planning_layer_active' => true,
                'code_execution_config' => ['language' => 'php'],
                'model_response_summary' => 'Calculates hours by querying the database for shifts in the same week, summing durations, and comparing to 40.',
                'time_limit_minutes'    => 60, 'is_published' => true, 'is_active' => true,
            ],
        );

        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $task->id, 'type' => 'code'],
            ['label' => 'Validation rule', 'description' => 'The code implementing the overtime check.', 'is_required' => true, 'display_order' => 1],
        );
        TaskExpectedDeliverableModel::updateOrCreate(
            ['task_id' => $task->id, 'type' => 'written_explanation'],
            ['label' => 'Calculation explanation', 'description' => 'Explain the logic used to determine weekly hours.', 'is_required' => true, 'display_order' => 2],
        );

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $task->id, 'complexity_level' => $level],
                [
                    'scenario_text'        => match($level) {
                        'low'  => "Implement the overtime validation. Query the database for all shifts the user has this week (Monday to Sunday) where the status is not 'rejected'. Sum the durations and verify it stays <= 40.",
                        'mid'  => "Implement the overtime validation to prevent exceeding 40 hours in a week. Pay attention to how you define 'week' and which shift statuses to include.",
                        'high' => "Design and implement the overtime validation rule. Consider edge cases around timezones, week boundaries, and shift statuses.",
                    },
                    'scaffolding_text_low' => "Consider: how do you calculate the start and end dates of the current week?",
                    'scaffolding_text_mid' => "Think about the query needed to accurately sum up the hours.",
                    'scaffolding_text_high'=> "No scaffolding.",
                    'context_text_low'     => "Full context provided.",
                    'context_text_mid'     => "Partial context provided.",
                    'context_text_high'    => "Sparse context.",
                ],
            );
        }

        RubricCriterionModel::updateOrCreate(
            ['rubric_set_id' => $rubricSet->id, 'task_id' => $task->id, 'task_dimension_label' => 'Validation accuracy'],
            [
                'parent_dimension_id'    => 'dim_problem_analysis',
                'complexity_level'       => 'mid',
                'criterion_text'         => 'The logic correctly defines the week boundaries, excludes rejected shifts, and sums the hours accurately.',
                'weight'                 => '1.000', 'dimension_weight' => '0.500',
                'claude_detection_hint'  => 'Look for startOfWeek() and endOfWeek() or similar date math, and a WHERE clause filtering out rejected statuses.',
                'distinguished_description' => 'Flawless calculation with explicit consideration of edge cases (e.g., shifts crossing midnight).',
                'proficient_description'    => 'Correctly sums hours for the week and checks the 40-hour limit.',
                'developing_description'    => 'Attempts to sum hours but might miss week boundaries or include rejected shifts.',
                'beginning_description'     => 'Fails to implement a functional hour calculation.',
                'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
            ],
        );

        BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Enforce weekly overtime limits'],
            [
                'description'       => 'Prevent users from claiming shifts that push them over 40 hours per week.',
                'default_priority'  => 'must_have', 'role_tags' => ['backend'],
                'task_id'           => $task->id, 'dependency_item_ids' => null, 'display_order' => 1,
            ],
        );
    }
}
