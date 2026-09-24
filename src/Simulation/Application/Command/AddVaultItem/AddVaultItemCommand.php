<?php

namespace Src\Simulation\Application\Command\AddVaultItem;

final class AddVaultItemCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly string $documentType,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $rankGate,
        public readonly ?string $phaseGate,
        public readonly bool $isReferenceDoc,
        public readonly int $displayOrder,
    ) {}
}
