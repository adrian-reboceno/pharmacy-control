<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Infrastructure/Persistence/Eloquent/Model/EloquentClassification.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentClassification extends Model
{
    use HasUuids;

    protected $table = 'medication_classifications';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'lgs_group', 'name', 'prescription_type',
        'validity_days', 'validity_note', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'validity_days' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
