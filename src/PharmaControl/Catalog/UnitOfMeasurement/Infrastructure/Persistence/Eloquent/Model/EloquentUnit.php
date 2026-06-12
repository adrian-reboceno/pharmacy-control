<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentUnit extends Model
{
    use HasUuids;

    protected $table = 'units_of_measurement';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'symbol', 'type', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
