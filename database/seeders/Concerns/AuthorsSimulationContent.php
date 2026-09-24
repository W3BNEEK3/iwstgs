<?php
namespace Database\Seeders\Concerns;

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
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskGuidancePromptModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

/**
 * Compact, re-run-safe (updateOrCreate throughout) authoring helpers for
 * simulation content seeders. Every helper writes exactly the columns the
 * content schema requires, so a seeder only states the content itself.
 */
trait AuthorsSimulationContent
{
    private const ALL_DIMENSIONS = [
        'dim_problem_analysis', 'dim_design', 'dim_implementation',
        'dim_testing', 'dim_debugging', 'dim_communication',
    ];

    /** @param array<string, float> $weights keyed by dimension id; must cover all six */
    protected function role(string $title, array $tags, int $minYears, array $weights, string $threshold = 'developing', bool $isLead = false): RoleDefinitionModel
    {
        return RoleDefinitionModel::updateOrCreate(['title' => $title], [
            'specialization_tags'  => $tags,
            'min_years_experience' => $minYears,
            'dimension_weights'    => $weights,
            'dimension_thresholds' => array_fill_keys(self::ALL_DIMENSIONS, $threshold),
            'is_lead_role'         => $isLead,
        ]);
    }

    /** @return array{0: ProjectTemplateModel, 1: RubricSetModel} */
    protected function project(string $title, array $attributes): array
    {
        $project = ProjectTemplateModel::updateOrCreate(['title' => $title], $attributes + [
            'is_published' => true,
            'is_active'    => true,
        ]);

        $rubricSet = $project->rubric_set_id !== null ? RubricSetModel::find($project->rubric_set_id) : null;
        if ($rubricSet === null) {
            $rubricSet = RubricSetModel::create(['project_id' => $project->id, 'version' => '1.0']);
            $project->update(['rubric_set_id' => $rubricSet->id]);
        }

        return [$project, $rubricSet];
    }

    protected function vaultItem(ProjectTemplateModel $project, string $documentType, string $title, string $content, int $order, string $phaseGate = 'pre_induction', ?string $rankGate = null): void
    {
        ArtifactVaultItemModel::updateOrCreate(
            ['project_id' => $project->id, 'document_type' => $documentType, 'rank_gate' => $rankGate],
            ['title' => $title, 'content' => $content, 'phase_gate' => $phaseGate, 'is_reference_doc' => true, 'display_order' => $order],
        );
    }

    /**
     * @param array{title: string, narrative: string, trigger: string, trigger_type: string, role_label: string, autonomy?: string} $attributes
     * @param array<array{type: string, title: string, content: string, signals?: string[]}> $materials
     */
    protected function scenario(ProjectTemplateModel $project, int $sequence, array $attributes, array $materials = [], bool $isDiagnostic = false): ScenarioTemplateModel
    {
        $scenario = ScenarioTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'sequence_order' => $sequence],
            [
                'title'                  => $attributes['title'],
                'narrative_context'      => $attributes['narrative'],
                'situation_trigger'      => $attributes['trigger'],
                'situation_trigger_type' => $attributes['trigger_type'],
                'learner_role_label'     => $attributes['role_label'],
                'default_autonomy_level' => $attributes['autonomy'] ?? 'mid',
                'is_diagnostic'          => $isDiagnostic,
                'is_published'           => ! $isDiagnostic,
                'is_active'              => true,
            ],
        );

        foreach ($materials as $i => $material) {
            ScenarioReferenceMaterialModel::updateOrCreate(
                ['scenario_id' => $scenario->id, 'title' => $material['title']],
                [
                    'material_type'    => $material['type'],
                    'content'          => $material['content'],
                    'embedded_signals' => $material['signals'] ?? [],
                    'display_order'    => $i + 1,
                ],
            );
        }

        return $scenario;
    }

    /**
     * Spec keys:
     *   title, brief, type (core|consequence|suggestion|diagnostic_scenario), domain, role_tags,
     *   tools?, concepts?, architectural?, time?, model_answer, fixed? ('low'|'mid'|'high' pins all CAC axes),
     *   deliverables: [[type, label, description, required?]],
     *   variants:     ['low' => text, 'mid' => text, 'high' => text]  — the brief as seen at each complexity,
     *   scaffolding:  ['low' => text, 'mid' => text, 'high' => text]  — guidance shown per autonomy level,
     *   context:      ['low' => text, 'mid' => text, 'high' => text]  — context note per fidelity level,
     *   criteria:     [[dimension, label, text, weight, dimension_weight, hint, [distinguished, proficient, developing, beginning], architectural?]],
     *   hints?:       [[dimension, text]] — proactive hints for low and mid autonomy learners.
     */
    protected function task(ScenarioTemplateModel $scenario, RubricSetModel $rubricSet, int $sequence, array $spec): TaskModel
    {
        $fixed = $spec['fixed'] ?? null;

        $task = TaskModel::updateOrCreate(
            ['scenario_id' => $scenario->id, 'sequence_order' => $sequence],
            [
                'title'                  => $spec['title'],
                'task_brief'             => $spec['brief'],
                'domain'                 => $spec['domain'],
                'task_type'              => $spec['type'] ?? 'core',
                'role_tags'              => $spec['role_tags'],
                'tools'                  => $spec['tools'] ?? [],
                'prerequisite_concepts'  => $spec['concepts'] ?? [],
                'is_cac_runtime_set'     => $fixed === null,
                'fixed_complexity'       => $fixed,
                'fixed_autonomy'         => $fixed,
                'fixed_context_fidelity' => $fixed,
                'is_architectural'       => $spec['architectural'] ?? false,
                'planning_layer_active'  => ($spec['type'] ?? 'core') !== 'diagnostic_scenario',
                'code_execution_config'  => null,
                'model_response_summary' => $spec['model_answer'],
                'time_limit_minutes'     => $spec['time'] ?? null,
                'is_published'           => true,
                'is_active'              => true,
            ],
        );

        TaskExpectedDeliverableModel::where('task_id', $task->id)->delete();
        foreach ($spec['deliverables'] as $i => $d) {
            TaskExpectedDeliverableModel::create([
                'task_id' => $task->id, 'type' => $d[0], 'label' => $d[1], 'description' => $d[2],
                'is_required' => $d[3] ?? true, 'display_order' => $i + 1,
            ]);
        }

        foreach (['low', 'mid', 'high'] as $level) {
            TaskCacVariantModel::updateOrCreate(
                ['task_id' => $task->id, 'complexity_level' => $level],
                [
                    'scenario_text'         => $spec['variants'][$level],
                    'scaffolding_text_low'  => $spec['scaffolding']['low'],
                    'scaffolding_text_mid'  => $spec['scaffolding']['mid'],
                    'scaffolding_text_high' => $spec['scaffolding']['high'],
                    'context_text_low'      => $spec['context']['low'],
                    'context_text_mid'      => $spec['context']['mid'],
                    'context_text_high'     => $spec['context']['high'],
                ],
            );
        }

        // One 'mid' row per criterion: EvaluationPromptBuilder falls back to all of a
        // task's criteria when none match the learner's complexity level.
        RubricCriterionModel::where('task_id', $task->id)->delete();
        foreach ($spec['criteria'] as $c) {
            RubricCriterionModel::create([
                'rubric_set_id'             => $rubricSet->id,
                'task_id'                   => $task->id,
                'task_dimension_label'      => $c[1],
                'parent_dimension_id'       => $c[0],
                'complexity_level'          => 'mid',
                'criterion_text'            => $c[2],
                'weight'                    => $c[3],
                'dimension_weight'          => $c[4],
                'claude_detection_hint'     => $c[5],
                'distinguished_description' => $c[6][0],
                'proficient_description'    => $c[6][1],
                'developing_description'    => $c[6][2],
                'beginning_description'     => $c[6][3],
                'is_architectural'          => $c[7] ?? false,
                'is_planning_layer'         => false,
                'reference_doc_anchor'      => null,
            ]);
        }

        TaskGuidancePromptModel::where('task_id', $task->id)->delete();
        foreach ($spec['hints'] ?? [] as $i => [$dimension, $text]) {
            foreach (['low', 'mid'] as $autonomy) {
                TaskGuidancePromptModel::create([
                    'task_id' => $task->id, 'trigger_dimension' => $dimension, 'prompt_text' => $text,
                    'autonomy_level_filter' => $autonomy, 'delivery_mode' => 'proactive', 'display_order' => $i + 1,
                ]);
            }
        }

        return $task;
    }

    protected function backlogItem(ProjectTemplateModel $project, string $title, string $description, string $priority, array $roleTags, ?TaskModel $task, int $order): BacklogItemTemplateModel
    {
        return BacklogItemTemplateModel::updateOrCreate(
            ['project_id' => $project->id, 'title' => $title],
            [
                'description' => $description, 'default_priority' => $priority, 'role_tags' => $roleTags,
                'task_id' => $task?->id, 'dependency_item_ids' => null, 'display_order' => $order,
            ],
        );
    }

    /**
     * Consequence tasks are injected when the core task fails; suggestion tasks
     * when a habit pattern is detected. Both must live in the same scenario as
     * the core task, since only current-scenario tasks can be submitted.
     */
    protected function linkAdaptive(TaskModel $core, array $consequences, array $suggestions = []): void
    {
        $core->update([
            'consequence_task_ids' => array_map(fn (TaskModel $t) => $t->id, $consequences),
            'suggestion_task_ids'  => array_map(fn (TaskModel $t) => $t->id, $suggestions),
        ]);
    }
}
