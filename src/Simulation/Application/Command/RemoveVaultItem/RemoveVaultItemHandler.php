<?php

namespace Src\Simulation\Application\Command\RemoveVaultItem;

use Src\Simulation\Domain\Vault\ArtifactVaultItemRepository;

final class RemoveVaultItemHandler
{
    public function __construct(private readonly ArtifactVaultItemRepository $repository) {}

    public function __invoke(RemoveVaultItemCommand $command): void
    {
        $this->repository->remove($command->id);
    }
}
