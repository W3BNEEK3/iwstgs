<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependencyModel extends Model
{
    use HasUuids;

    protected $table = 'task_dependencies';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'prerequisite_task_id',
    ];

    // The task that HAS the prerequisite.
    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    // The task that MUST be done first. Same table, different foreign key —
    // this is a self-referential relationship, so we name the method for the
    // role it plays rather than the table it points at.
    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'prerequisite_task_id');
    }
}
