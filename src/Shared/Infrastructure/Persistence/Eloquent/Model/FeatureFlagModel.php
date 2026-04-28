<?php

namespace Src\Shared\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the feature_flags table.
 *
 * This is an infrastructure concern — it knows about the database.
 * Application and domain code must NEVER import this class directly.
 * They work with FeatureFlag (domain object) obtained through FeatureFlagRepository.
 *
 * The separation means we could swap MySQL for Redis-backed flags later
 * without touching a single domain or application class.
 */
class FeatureFlagModel extends Model
{
    protected $table = 'feature_flags';

    protected $fillable = [
        'flag_key',
        'description',
        'is_enabled',
        'module',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
