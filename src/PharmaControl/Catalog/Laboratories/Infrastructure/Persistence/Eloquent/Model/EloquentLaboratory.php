<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Infrastructure/Persistence/Eloquent/Model/EloquentLaboratory.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentLaboratory extends Model
{
    use HasUuids;

    protected $table = 'laboratories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'country_code',
        'website',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
