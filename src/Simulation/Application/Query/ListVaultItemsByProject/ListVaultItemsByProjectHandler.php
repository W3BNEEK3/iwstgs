<?php

namespace Src\Simulation\Application\Query\ListVaultItemsByProject;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Vault\ArtifactVaultItem;
use Src\Simulation\Domain\Vault\ArtifactVaultItemRepository;

final class ListVaultItemsByProjectHandler
{
    public function __construct(private readonly ArtifactVaultItemRepository $repository) {}

    /**
     * @return ArtifactVaultItem[]
     */
    public function __invoke(ListVaultItemsByProjectQuery $query): array
    {
        return $this->repository->findByProject(ProjectTemplateId::fromString($query->projectId));
    }
}
