<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Infrastructure/Persistence/Eloquent/Model/EloquentCategory.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentCategory extends Model
{
    use HasUuids;

    protected $table = 'categories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'parent_id', 'name', 'slug', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
