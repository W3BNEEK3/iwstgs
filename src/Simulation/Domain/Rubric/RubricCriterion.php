<?php
namespace Src\Simulation\Domain\Rubric;

use Src\Shared\Domain\AggregateRoot;
use Src\Simulation\Domain\Cac\CacLevel;

final class RubricCriterion extends AggregateRoot
{
    public function __construct(
        private readonly RubricCriterionId $id,
        private readonly string $rubricSetId,
        private readonly string $taskId,
        private string  $taskDimensionLabel,
        private string  $parentDimensionId,
        private CacLevel $complexityLevel,
        private string  $criterionText,
        private string  $weight,           // decimal(4,3) — kept as string to preserve precision
        private string  $dimensionWeight,  // decimal(4,3)
        private string  $claudeDetectionHint,
        private string  $distinguishedDescription,
        private string  $proficientDescription,
        private string  $developingDescription,
        private string  $beginningDescription,
        private bool    $isArchitectural,
        private bool    $isPlanningLayer,
        private ?string $referenceDocAnchor,
    ) {}

    public static function create(
        RubricCriterionId $id, string $rubricSetId, string $taskId, string $taskDimensionLabel,
        string $parentDimensionId, CacLevel $complexityLevel, string $criterionText,
        string $weight, string $dimensionWeight, string $claudeDetectionHint,
        string $distinguishedDescription, string $proficientDescription,
        string $developingDescription, string $beginningDescription,
        bool $isArchitectural = false, bool $isPlanningLayer = false, ?string $referenceDocAnchor = null,
    ): self {
        $c = new self(
            $id, $rubricSetId, $taskId, $taskDimensionLabel, $parentDimensionId, $complexityLevel,
            $criterionText, $weight, $dimensionWeight, $claudeDetectionHint,
            $distinguishedDescription, $proficientDescription, $developingDescription, $beginningDescription,
            $isArchitectural, $isPlanningLayer, $referenceDocAnchor,
        );
        $c->recordEvent(new RubricCriterionCreated((string) $id, $taskId, $parentDimensionId));
        return $c;
    }

    public static function reconstitute(
        RubricCriterionId $id, string $rubricSetId, string $taskId, string $taskDimensionLabel,
        string $parentDimensionId, CacLevel $complexityLevel, string $criterionText,
        string $weight, string $dimensionWeight, string $claudeDetectionHint,
        string $distinguishedDescription, string $proficientDescription,
        string $developingDescription, string $beginningDescription,
        bool $isArchitectural, bool $isPlanningLayer, ?string $referenceDocAnchor,
    ): self {
        return new self(
            $id, $rubricSetId, $taskId, $taskDimensionLabel, $parentDimensionId, $complexityLevel,
            $criterionText, $weight, $dimensionWeight, $claudeDetectionHint,
            $distinguishedDescription, $proficientDescription, $developingDescription, $beginningDescription,
            $isArchitectural, $isPlanningLayer, $referenceDocAnchor,
        );
    }

    public function updateText(string $criterionText): void { $this->criterionText = $criterionText; }
    
    public function reweight(string $weight, string $dimensionWeight): void
    {
        $this->weight = $weight;
        $this->dimensionWeight = $dimensionWeight;
    }

    public function id(): string { return (string) $this->id; }
    public function taskId(): string { return $this->taskId; }

    public function toPrimitives(): array
    {
        return [
            'id' => (string) $this->id,
            'rubric_set_id' => $this->rubricSetId,
            'task_id' => $this->taskId,
            'task_dimension_label' => $this->taskDimensionLabel,
            'parent_dimension_id' => $this->parentDimensionId,
            'complexity_level' => $this->complexityLevel->value,
            'criterion_text' => $this->criterionText,
            'weight' => $this->weight,
            'dimension_weight' => $this->dimensionWeight,
            'claude_detection_hint' => $this->claudeDetectionHint,
            'distinguished_description' => $this->distinguishedDescription,
            'proficient_description' => $this->proficientDescription,
            'developing_description' => $this->developingDescription,
            'beginning_description' => $this->beginningDescription,
            'is_architectural' => $this->isArchitectural,
            'is_planning_layer' => $this->isPlanningLayer,
            'reference_doc_anchor' => $this->referenceDocAnchor,
        ];
    }
}
