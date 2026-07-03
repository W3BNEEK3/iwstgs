<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Simulation\Domain\ConceptTag\ConceptTagCategory;

class ConceptTagModel extends Model
{
    use HasUuids;

    protected $table = 'concept_tags';

    protected $fillable = [
        'id',
        'tag',
        'category',
        'description',
        'source',
    ];

    protected $casts = [
        'category' => ConceptTagCategory::class,
    ];

    public function knowledgeAnchors(): HasMany
    {
        return $this->hasMany(TaskKnowledgeAnchorModel::class, 'concept_id');
    }
}
