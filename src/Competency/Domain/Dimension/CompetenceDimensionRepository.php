<?php
namespace Src\Competency\Domain\Dimension;

interface CompetenceDimensionRepository
{
    /** @return CompetenceDimensionSummary[] */
    public function all(): array;
}
