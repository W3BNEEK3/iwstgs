<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerModel extends Model
{
    use HasUuids;

    protected $table = 'learners';

    protected $fillable = [
        'id',
        'user_id',
        'organisation_id',
        'entry_category',
        'years_experience',
        'last_active_at',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'last_active_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}