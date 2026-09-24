<?php

namespace Src\Competency\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

class CompetenceDimensionModel extends Model
{
    protected $table = 'competence_dimensions';

    // String primary key, not an auto-incrementing integer and not a UUID.
    protected $keyType = 'string';
    public $incrementing = false;

    // This table has no created_at / updated_at columns.
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'short_label',
        'core_question',
        'observable_indicators',
        'sequence_order',
    ];

    protected $casts = [
        'observable_indicators' => 'array',
        'sequence_order'        => 'integer',
    ];
}
