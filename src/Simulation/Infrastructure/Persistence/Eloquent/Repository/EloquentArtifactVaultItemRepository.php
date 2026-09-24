<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Vault\ArtifactVaultItem;
use Src\Simulation\Domain\Vault\ArtifactVaultItemRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper\ArtifactVaultItemMapper;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ArtifactVaultItemModel;

final class EloquentArtifactVaultItemRepository implements ArtifactVaultItemRepository
{
    public function __construct(private readonly ArtifactVaultItemMapper $mapper) {}

    public function save(ArtifactVaultItem $item): void
    {
        ArtifactVaultItemModel::updateOrCreate(
            ['id' => $item->getId()],
            $item->toPrimitives()
        );
    }

    public function findById(string $id): ?ArtifactVaultItem
    {
        $model = ArtifactVaultItemModel::find($id);
        return $model ? $this->mapper->toDomain($model) : null;
    }

    /**
     * @return ArtifactVaultItem[]
     */
    public function findByProject(ProjectTemplateId $projectId): array
    {
        $models = ArtifactVaultItemModel::where('project_id', (string) $projectId)
            ->orderBy('display_order')
            ->get();
            
        return $models->map(fn ($m) => $this->mapper->toDomain($m))->all();
    }

    public function remove(string $id): void
    {
        ArtifactVaultItemModel::destroy($id);
    }
}
