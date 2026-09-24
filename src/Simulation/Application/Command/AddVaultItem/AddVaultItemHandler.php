<?php

namespace Src\Simulation\Application\Command\AddVaultItem;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Vault\ArtifactVaultItem;
use Src\Simulation\Domain\Vault\ArtifactVaultItemRepository;
use Src\Simulation\Domain\Vault\DocumentType;
use Src\Simulation\Domain\Vault\PhaseGate;

final class AddVaultItemHandler
{
    public function __construct(private readonly ArtifactVaultItemRepository $repository) {}

    public function __invoke(AddVaultItemCommand $command): void
    {
        $item = ArtifactVaultItem::create(
            $command->id,
            ProjectTemplateId::fromString($command->projectId),
            DocumentType::from($command->documentType),
            $command->title,
            $command->content,
            $command->rankGate,
            $command->phaseGate ? PhaseGate::from($command->phaseGate) : null,
            $command->isReferenceDoc,
            $command->displayOrder
        );

        $this->repository->save($item);
    }
}
