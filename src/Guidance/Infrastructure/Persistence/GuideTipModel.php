<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GuideTipModel extends Model
{
    use HasUuids;

    protected $table = 'guide_tips';
    protected $guarded = [];

    protected $casts = ['pages' => 'array', 'is_active' => 'boolean'];
}
