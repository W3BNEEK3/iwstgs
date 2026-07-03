<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricSetModel extends Model
{
    use HasUuids;

    protected $table = 'rubric_sets';

    protected $fillable = [
        'id', 'project_id', 'version',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterionModel::class, 'rubric_set_id');
    }
}
