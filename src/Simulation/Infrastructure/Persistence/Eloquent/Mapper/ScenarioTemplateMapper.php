<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Scenario\MaterialType;
use Src\Simulation\Domain\Scenario\ReferenceMaterial;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\SituationTriggerType;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;

final class ScenarioTemplateMapper
{
    public function toEntity(ScenarioTemplateModel $m): ScenarioTemplate
    {
        $materials = $m->referenceMaterials->map(fn ($rm) => new ReferenceMaterial(
            id: $rm->id,
            type: $rm->material_type instanceof MaterialType ? $rm->material_type : MaterialType::from($rm->material_type),
            title: $rm->title,
            content: $rm->content,
            embeddedSignals: $rm->embedded_signals,
            displayOrder: (int) $rm->display_order,
        ))->all();

        return ScenarioTemplate::reconstitute(
            id: ScenarioTemplateId::fromString($m->id),
            projectId: $m->project_id,
            sequenceOrder: (int) $m->sequence_order,
            title: $m->title,
            narrativeContext: $m->narrative_context,
            situationTrigger: $m->situation_trigger,
            situationTriggerType: $m->situation_trigger_type instanceof SituationTriggerType ? $m->situation_trigger_type : SituationTriggerType::from($m->situation_trigger_type),
            learnerRoleLabel: $m->learner_role_label,
            defaultAutonomyLevel: $m->default_autonomy_level instanceof CacLevel ? $m->default_autonomy_level : CacLevel::from($m->default_autonomy_level),
            isDiagnostic: (bool) $m->is_diagnostic,
            isPublished: (bool) $m->is_published,
            isActive: (bool) $m->is_active,
            referenceMaterials: $materials,
        );
    }
}
