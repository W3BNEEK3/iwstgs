<?php
namespace Src\Competency\Domain\Dimension;

final class CompetenceDimensionSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $shortLabel,
        public readonly string $coreQuestion,
    ) {}
}
