<?php

namespace Src\Organizations\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganisationModel extends Model
{
    use HasUuids;

    protected $table = 'organisations';
    protected $fillable = ['id', 'name', 'slug', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
