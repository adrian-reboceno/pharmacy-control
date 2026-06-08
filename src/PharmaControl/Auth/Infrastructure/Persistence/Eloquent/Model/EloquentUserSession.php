<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Model/EloquentUserSession.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

class EloquentUserSession extends Model
{
    protected $table = 'user_sessions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id', 'user_id', 'client_type', 'access_token_hash', 'refresh_token_hash',
        'active_role_id', 'active_branch_id', 'access_expires_at', 'refresh_expires_at',
        'last_activity_at', 'role_activated_at', 'ip_address', 'user_agent',
        'revoked_at', 'created_at',
    ];

    protected $casts = [
        'access_expires_at' => 'immutable_datetime',
        'refresh_expires_at' => 'immutable_datetime',
        'last_activity_at' => 'immutable_datetime',
        'role_activated_at' => 'immutable_datetime',
        'revoked_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
    ];
}
