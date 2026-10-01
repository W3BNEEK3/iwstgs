<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GuideMessageModel extends Model
{
    use HasUuids;

    protected $table = 'guide_messages';
    protected $guarded = [];

    protected $casts = ['facts' => 'array', 'shown_at' => 'datetime', 'dismissed_at' => 'datetime'];
}
