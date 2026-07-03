<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacklogItemTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'backlog_item_templates';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'project_id',
        'title',
        'description',
        'default_priority',
        'role_tags',
        'task_id',
        'dependency_item_ids',
        'display_order',
    ];

    protected $casts = [
        'role_tags'           => 'array',
        'dependency_item_ids' => 'array',
        'display_order'       => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
