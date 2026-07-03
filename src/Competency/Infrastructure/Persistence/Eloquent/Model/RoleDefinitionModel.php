<?php

namespace Src\Competency\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RoleDefinitionModel extends Model
{
    use HasUuids;

    protected $table = 'role_definitions';

    protected $fillable = [
        'id',
        'title',
        'specialization_tags',
        'min_years_experience',
        'dimension_weights',
        'dimension_thresholds',
        'is_lead_role',
    ];

    protected $casts = [
        'specialization_tags'  => 'array',
        'dimension_weights'    => 'array',
        'dimension_thresholds' => 'array',
        'min_years_experience' => 'integer',
        'is_lead_role'         => 'boolean',
    ];
}
