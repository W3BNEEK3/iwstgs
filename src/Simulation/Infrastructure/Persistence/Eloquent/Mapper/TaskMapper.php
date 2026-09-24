<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Task\CacVariant;
use Src\Simulation\Domain\Task\DeliverableType;
use Src\Simulation\Domain\Task\DeliveryMode;
use Src\Simulation\Domain\Task\ExpectedDeliverable;
use Src\Simulation\Domain\Task\GuidancePrompt;
use Src\Simulation\Domain\Task\KnowledgeAnchor;
use Src\Simulation\Domain\Task\Task;
use Src\Simulation\Domain\Task\TaskDependencyLink;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskType;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

final class TaskMapper
{
    public function toEntity(TaskModel $m): Task
    {
        // Map each of the five eager-loaded relation collections into child objects.

        $expectedDeliverables = $m->expectedDeliverables->map(fn ($d) => new ExpectedDeliverable(
            id:           $d->id,
            type:         $d->type instanceof DeliverableType ? $d->type : DeliverableType::from($d->type),
            label:        $d->label,
            description:  $d->description,
            isRequired:   (bool) $d->is_required,
            displayOrder: (int) $d->display_order,
        ))->all();

        $cacVariants = $m->cacVariants->map(fn ($v) => new CacVariant(
            id:                  $v->id,
            complexityLevel:     $v->complexity_level instanceof CacLevel ? $v->complexity_level : CacLevel::from($v->complexity_level),
            scenarioText:        $v->scenario_text,
            scaffoldingTextLow:  $v->scaffolding_text_low,
            scaffoldingTextMid:  $v->scaffolding_text_mid,
            scaffoldingTextHigh: $v->scaffolding_text_high,
            contextTextLow:      $v->context_text_low,
            contextTextMid:      $v->context_text_mid,
            contextTextHigh:     $v->context_text_high,
        ))->all();

        $dependencies = $m->dependencies->map(fn ($d) => new TaskDependencyLink(
            id:                 $d->id,
            prerequisiteTaskId: $d->prerequisite_task_id,
        ))->all();

        $knowledgeAnchors = $m->knowledgeAnchors->map(fn ($a) => new KnowledgeAnchor(
            id:                     $a->id,
            conceptId:              $a->concept_id,
            conceptName:            $a->concept_name,
            domain:                 $a->domain,
            applicationExpectation: $a->application_expectation,
            isRequired:             (bool) $a->is_required,
            remediationHint:        $a->remediation_hint,
        ))->all();

        $guidancePrompts = $m->guidancePrompts->map(fn ($p) => new GuidancePrompt(
            id:                  $p->id,
            triggerDimension:    $p->trigger_dimension,
            promptText:          $p->prompt_text,
            autonomyLevelFilter: $p->autonomy_level_filter instanceof CacLevel
                ? $p->autonomy_level_filter
                : ($p->autonomy_level_filter ? CacLevel::from($p->autonomy_level_filter) : null),
            deliveryMode:        $p->delivery_mode instanceof DeliveryMode ? $p->delivery_mode : DeliveryMode::from($p->delivery_mode),
            displayOrder:        (int) $p->display_order,
        ))->all();

        return Task::reconstitute(
            id:                  TaskId::fromString($m->id),
            scenarioId:          $m->scenario_id,
            sequenceOrder:       (int) $m->sequence_order,
            title:               $m->title,
            taskBrief:           $m->task_brief,
            taskType:            $m->task_type instanceof TaskType ? $m->task_type : TaskType::from($m->task_type),
            isCacRuntimeSet:     (bool) $m->is_cac_runtime_set,
            isArchitectural:     (bool) $m->is_architectural,
            planningLayerActive: (bool) $m->planning_layer_active,
            isPublished:         (bool) $m->is_published,
            isActive:            (bool) $m->is_active,
            domain:              $m->domain,
            roleTags:            $m->role_tags ?? [],
            tools:               $m->tools ?? [],
            prerequisiteConcepts:$m->prerequisite_concepts ?? [],
            fixedComplexity:     $m->fixed_complexity instanceof CacLevel ? $m->fixed_complexity : ($m->fixed_complexity ? CacLevel::from($m->fixed_complexity) : null),
            fixedAutonomy:       $m->fixed_autonomy instanceof CacLevel ? $m->fixed_autonomy : ($m->fixed_autonomy ? CacLevel::from($m->fixed_autonomy) : null),
            fixedContextFidelity:$m->fixed_context_fidelity instanceof CacLevel ? $m->fixed_context_fidelity : ($m->fixed_context_fidelity ? CacLevel::from($m->fixed_context_fidelity) : null),
            consequenceTaskIds:  $m->consequence_task_ids ?? [],
            suggestionTaskIds:   $m->suggestion_task_ids ?? [],
            codeExecutionConfig: $m->code_execution_config,
            modelResponseSummary:$m->model_response_summary,
            timeLimitMinutes:    $m->time_limit_minutes,
            expectedDeliverables:$expectedDeliverables,
            cacVariants:         $cacVariants,
            dependencies:        $dependencies,
            knowledgeAnchors:    $knowledgeAnchors,
            guidancePrompts:     $guidancePrompts,
        );
    }
}
