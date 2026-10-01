<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FeatureAnnouncementModel extends Model
{
    use HasUuids;

    protected $table = 'feature_announcements';
    protected $guarded = [];

    protected $casts = ['published_at' => 'datetime'];
}
