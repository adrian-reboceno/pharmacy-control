<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentLocation extends Model
{
    use HasUuids;

    protected $table = 'locations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'level', 'parent_id', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
