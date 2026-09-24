<?php
namespace Src\Simulation\Application\Command\AddReferenceMaterial;

final class AddReferenceMaterialCommand
{
    public function __construct(
        public readonly string $scenarioId,
        public readonly string $materialType,
        public readonly string $title,
        public readonly string $content,
        public readonly int    $displayOrder = 0,
    ) {}
}
