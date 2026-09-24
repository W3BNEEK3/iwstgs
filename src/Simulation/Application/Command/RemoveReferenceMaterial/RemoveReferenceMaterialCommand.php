<?php
namespace Src\Simulation\Application\Command\RemoveReferenceMaterial;

final class RemoveReferenceMaterialCommand
{
    public function __construct(
        public readonly string $scenarioId,
        public readonly string $materialId,
    ) {}
}
