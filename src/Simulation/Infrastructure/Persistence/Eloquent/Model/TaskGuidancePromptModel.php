<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Task\DeliveryMode;

class TaskGuidancePromptModel extends Model
{
    use HasUuids;

    protected $table = 'task_guidance_prompts';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'trigger_dimension', 'prompt_text',
        'autonomy_level_filter', 'delivery_mode', 'display_order',
    ];

    protected $casts = [
        // §1 Phase-3 deferrals — activated in 4c
        'delivery_mode'         => DeliveryMode::class,
        'autonomy_level_filter' => CacLevel::class,
        'display_order'         => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
