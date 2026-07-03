<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtifactVaultItemModel extends Model
{
    use HasUuids;

    protected $table = 'artifact_vault_items';

    protected $fillable = [
        'id',
        'project_id',
        'document_type',
        'title',
        'content',
        'rank_gate',
        'phase_gate',
        'is_reference_doc',
        'display_order',
    ];

    protected $casts = [
        'is_reference_doc' => 'boolean',
        'display_order'    => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }
}
