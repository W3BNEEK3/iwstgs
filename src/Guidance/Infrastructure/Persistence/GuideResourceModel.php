<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GuideResourceModel extends Model
{
    use HasUuids;

    protected $table = 'guide_resources';
    protected $guarded = [];

    protected $casts = ['dimensions' => 'array', 'is_free' => 'boolean', 'is_active' => 'boolean', 'last_check_ok' => 'boolean', 'last_checked_at' => 'datetime'];
}
