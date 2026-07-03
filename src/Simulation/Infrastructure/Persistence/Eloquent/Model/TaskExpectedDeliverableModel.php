<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskExpectedDeliverableModel extends Model
{
    use HasUuids;

    protected $table = 'task_expected_deliverables';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'type', 'label', 'description', 'is_required', 'display_order',
    ];

    protected $casts = [
        'is_required'   => 'boolean',
        'display_order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
