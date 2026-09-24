<?php
namespace Src\Simulation\Domain\Scenario;

interface ScenarioTemplateRepository
{
    public function save(ScenarioTemplate $scenario): void;
    public function findById(ScenarioTemplateId $id): ?ScenarioTemplate;
    /** @return ScenarioTemplate[] */
    public function findByProject(string $projectId): array;
    public function nextSequenceOrder(string $projectId): int; // Decision 4 helper

    /**
     * System-wide lookup, not project-scoped — the diagnostic pathway runs
     * once per learner globally, before any project is chosen, so there is
     * exactly one canonical diagnostic scenario across the whole platform
     * for MVP (Implementation Plan §5.5).
     */
    public function findDiagnosticScenario(): ?ScenarioTemplate;
}
