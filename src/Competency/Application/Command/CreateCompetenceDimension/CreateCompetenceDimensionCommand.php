<?php
namespace Src\Competency\Application\Command\CreateCompetenceDimension;

final class CreateCompetenceDimensionCommand
{
    /** @param string[] $observableIndicators */
    public function __construct(
        public readonly string $name,
        public readonly string $shortLabel,
        public readonly string $coreQuestion,
        public readonly array $observableIndicators,
    ) {}
}
