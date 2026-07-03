<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCacVariantModel extends Model
{
    use HasUuids;

    protected $table = 'task_cac_variants';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'complexity_level', 'scenario_text',
        'scaffolding_text_low', 'scaffolding_text_mid', 'scaffolding_text_high',
        'context_text_low', 'context_text_mid', 'context_text_high',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
