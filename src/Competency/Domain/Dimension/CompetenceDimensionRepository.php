<?php
namespace Src\Competency\Domain\Dimension;

interface CompetenceDimensionRepository
{
    /** @return CompetenceDimensionSummary[] */
    public function all(): array;

    public function nextSequenceOrder(): int;

    /** @param string[] $observableIndicators */
    public function create(
        string $id,
        string $name,
        string $shortLabel,
        string $coreQuestion,
        array $observableIndicators,
        int $sequenceOrder,
    ): void;
}
