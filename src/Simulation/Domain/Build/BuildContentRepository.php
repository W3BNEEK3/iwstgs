<?php
namespace Src\Simulation\Domain\Build;

/** Read side of the v2 build content: stack variants, per-variant milestone specs and scripted events. */
interface BuildContentRepository
{
    /** @return StackVariant[] easiest first */
    public function variantsForProject(string $projectId, bool $publishedOnly): array;

    public function findVariant(string $variantId): ?StackVariant;

    public function findSpec(string $taskId, string $variantId): ?TaskVariantSpec;

    /** @return array<string, string[]> task id => acceptance test IDs it introduces, for every task with a spec in this variant */
    public function acceptanceTestsByTask(string $variantId): array;

    /** @return ScenarioEvent[] in display order */
    public function eventsForScenario(string $scenarioId): array;
}
