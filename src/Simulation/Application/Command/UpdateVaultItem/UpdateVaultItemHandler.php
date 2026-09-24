<?php

namespace Src\Simulation\Application\Command\UpdateVaultItem;

use InvalidArgumentException;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Vault\ArtifactVaultItem;
use Src\Simulation\Domain\Vault\ArtifactVaultItemRepository;
use Src\Simulation\Domain\Vault\DocumentType;
use Src\Simulation\Domain\Vault\PhaseGate;

final class UpdateVaultItemHandler
{
    public function __construct(private readonly ArtifactVaultItemRepository $repository) {}

    public function __invoke(UpdateVaultItemCommand $command): void
    {
        $existing = $this->repository->findById($command->id);
        if (!$existing) {
            throw new InvalidArgumentException("Vault item not found.");
        }

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
