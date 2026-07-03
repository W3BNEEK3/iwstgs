<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioReferenceMaterialModel extends Model
{
    use HasUuids;

    protected $table = 'scenario_reference_materials';

    protected $fillable = [
        'id',
        'scenario_id',
        'material_type',
        'title',
        'content',
        'embedded_signals',
        'display_order',
    ];

    protected $casts = [
        'embedded_signals' => 'array',
        'display_order'    => 'integer',
        'material_type'    => \Src\Simulation\Domain\Scenario\MaterialType::class,
        'embedded_signals' => 'array',   // (already present from Phase 3)

    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(ScenarioTemplateModel::class, 'scenario_id');
    }
}
