<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentRoute extends Model
{
    use HasUuids;

    protected $table        = 'routes_of_administration';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'name', 'code', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
