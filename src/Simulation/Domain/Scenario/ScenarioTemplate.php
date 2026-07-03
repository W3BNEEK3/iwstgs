<?php
namespace Src\Simulation\Domain\Scenario;

use Src\Shared\Domain\AggregateRoot;
use Src\Simulation\Domain\Cac\CacLevel;

final class ScenarioTemplate extends AggregateRoot
{
    /** @param ReferenceMaterial[] $referenceMaterials */
    public function __construct(
        private readonly ScenarioTemplateId $id,
        private readonly string   $projectId,
        private int               $sequenceOrder,
        private string            $title,
        private string            $narrativeContext,
        private string            $situationTrigger,
        private SituationTriggerType $situationTriggerType,
        private ?string           $learnerRoleLabel,
        private CacLevel          $defaultAutonomyLevel,
        private bool              $isDiagnostic,
        private bool              $isPublished,
        private bool              $isActive,
        private array             $referenceMaterials = [],
    ) {}

    public static function create(
        ScenarioTemplateId $id,
        string $projectId,
        int $sequenceOrder,
        string $title,
        string $narrativeContext,
        string $situationTrigger,
        SituationTriggerType $situationTriggerType,
        CacLevel $defaultAutonomyLevel,
        ?string $learnerRoleLabel = null,
        bool $isDiagnostic = false,
    ): self {
        $s = new self(
            $id, $projectId, $sequenceOrder, $title, $narrativeContext,
            $situationTrigger, $situationTriggerType, $learnerRoleLabel,
            $defaultAutonomyLevel, $isDiagnostic,
            isPublished: false, isActive: true,
        );
        $s->recordEvent(new ScenarioTemplateCreated((string) $id, $projectId, $title));
        return $s;
    }

    public static function reconstitute(
        ScenarioTemplateId $id, string $projectId, int $sequenceOrder, string $title,
        string $narrativeContext, string $situationTrigger,
        SituationTriggerType $situationTriggerType, ?string $learnerRoleLabel,
        CacLevel $defaultAutonomyLevel, bool $isDiagnostic, bool $isPublished,
        bool $isActive, array $referenceMaterials,
    ): self {
        return new self(
            $id, $projectId, $sequenceOrder, $title, $narrativeContext,
            $situationTrigger, $situationTriggerType, $learnerRoleLabel,
            $defaultAutonomyLevel, $isDiagnostic, $isPublished, $isActive,
            $referenceMaterials,
        );
    }

    // --- child management (the aggregate is the consistency boundary) ---
    public function addReferenceMaterial(ReferenceMaterial $m): void
    {
        $this->referenceMaterials[] = $m;
    }

    public function removeReferenceMaterial(string $materialId): void
    {
        $this->referenceMaterials = array_values(array_filter(
            $this->referenceMaterials,
            fn (ReferenceMaterial $m) => $m->id() !== $materialId,
        ));
    }

    // --- own behaviour ---
    public function rename(string $t): void { $this->title = $t; }
    public function publish(): void   { $this->isPublished = true; }
    public function unpublish(): void { $this->isPublished = false; }

    public function id(): string { return (string) $this->id; }
    public function scenarioTemplateId(): ScenarioTemplateId { return $this->id; }
    public function isPublished(): bool { return $this->isPublished; }
    /** @return ReferenceMaterial[] */
    public function referenceMaterials(): array { return $this->referenceMaterials; }

    public function toPrimitives(): array
    {
        return [
            'id'                     => (string) $this->id,
            'project_id'             => $this->projectId,
            'sequence_order'         => $this->sequenceOrder,
            'title'                  => $this->title,
            'narrative_context'      => $this->narrativeContext,
            'situation_trigger'      => $this->situationTrigger,
            'situation_trigger_type' => $this->situationTriggerType->value,
            'learner_role_label'     => $this->learnerRoleLabel,
            'default_autonomy_level' => $this->defaultAutonomyLevel->value,
            'is_diagnostic'          => $this->isDiagnostic,
            'is_published'           => $this->isPublished,
            'is_active'              => $this->isActive,
        ];
    }
}
