<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Rubric\RubricCriterion;
use Src\Simulation\Domain\Rubric\RubricCriterionId;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;

final class RubricCriterionMapper
{
    public function toEntity(RubricCriterionModel $m): RubricCriterion
    {
        return RubricCriterion::reconstitute(
            id:                       RubricCriterionId::fromString($m->id),
            rubricSetId:              $m->rubric_set_id,
            taskId:                   $m->task_id,
            taskDimensionLabel:       $m->task_dimension_label,
            parentDimensionId:        $m->parent_dimension_id,
            complexityLevel:          $m->complexity_level instanceof CacLevel ? $m->complexity_level : CacLevel::from($m->complexity_level),
            criterionText:            $m->criterion_text,
            weight:                   (string) $m->weight,
            dimensionWeight:          (string) $m->dimension_weight,
            claudeDetectionHint:      $m->claude_detection_hint,
            distinguishedDescription: $m->distinguished_description,
            proficientDescription:    $m->proficient_description,
            developingDescription:    $m->developing_description,
            beginningDescription:     $m->beginning_description,
            isArchitectural:          (bool) $m->is_architectural,
            isPlanningLayer:          (bool) $m->is_planning_layer,
            referenceDocAnchor:       $m->reference_doc_anchor,
        );
    }
}
