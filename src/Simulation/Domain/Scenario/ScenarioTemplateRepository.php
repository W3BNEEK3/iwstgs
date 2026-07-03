<?php
namespace Src\Simulation\Domain\Scenario;

interface ScenarioTemplateRepository
{
    public function save(ScenarioTemplate $scenario): void;
    public function findById(ScenarioTemplateId $id): ?ScenarioTemplate;
    /** @return ScenarioTemplate[] */
    public function findByProject(string $projectId): array;
    public function nextSequenceOrder(string $projectId): int; // Decision 4 helper
}
