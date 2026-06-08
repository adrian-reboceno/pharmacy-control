<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Model/EloquentAuditLog.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

class EloquentAuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id', 'user_id', 'user_email', 'user_role', 'branch_id', 'module', 'action',
        'entity_type', 'entity_id', 'old_values', 'new_values', 'metadata',
        'ip_address', 'user_agent', 'status', 'timestamp',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'timestamp' => 'immutable_datetime',
    ];
}
