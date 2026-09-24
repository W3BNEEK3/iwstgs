<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class GuidePreferenceModel extends Model
{
    protected $table = 'user_guide_preferences';
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['user_id', 'is_enabled', 'dismissed_steps'];

    protected $casts = [
        'is_enabled'      => 'boolean',
        'dismissed_steps' => 'array',
    ];
}
