<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Simulation\Domain\Vault\ArtifactVaultItem;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ArtifactVaultItemModel;

final class ArtifactVaultItemMapper
{
    public function toDomain(ArtifactVaultItemModel $model): ArtifactVaultItem
    {
        return ArtifactVaultItem::reconstitute(
            $model->id,
            $model->project_id,
            $model->document_type->value,
            $model->title,
            $model->content,
            $model->rank_gate,
            $model->phase_gate?->value,
            $model->is_reference_doc,
            $model->display_order
        );
    }
}
