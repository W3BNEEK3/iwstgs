<?php

namespace Src\Simulation\Domain\Vault;

use Src\Simulation\Domain\Project\ProjectTemplateId;

interface ArtifactVaultItemRepository
{
    public function save(ArtifactVaultItem $item): void;

    public function findById(string $id): ?ArtifactVaultItem;

    /**
     * @return ArtifactVaultItem[]
     */
    public function findByProject(ProjectTemplateId $projectId): array;

    public function remove(string $id): void;
}
