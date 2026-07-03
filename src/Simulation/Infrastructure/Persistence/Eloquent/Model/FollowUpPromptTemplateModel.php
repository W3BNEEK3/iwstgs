<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FollowUpPromptTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'follow_up_prompt_templates';
    public $timestamps = false;

    protected $fillable = [
        'id', 'domain', 'prompt_text', 'trigger_condition', 'is_anti_copy',
    ];

    protected $casts = [
        'is_anti_copy' => 'boolean',
    ];
}
