<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Model/EloquentUser.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentUser extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = ['password_hash', 'two_factor_secret'];

    protected $fillable = [
        'id', 'email', 'password_hash', 'first_name', 'last_name', 'phone',
        'status', 'two_factor_enabled', 'two_factor_secret', 'must_change_password',
        'password_changed_at', 'failed_login_attempts', 'locked_until',
        'last_login_at', 'last_activity_at', 'email_verified_at',
        'email_verification_token', 'created_by',
    ];

    protected $casts = [
        'locked_until' => 'immutable_datetime',
        'password_changed_at' => 'immutable_datetime',
        'email_verified_at' => 'immutable_datetime',
        'last_login_at' => 'immutable_datetime',
        'last_activity_at' => 'immutable_datetime',
        'deleted_at' => 'immutable_datetime',
        'two_factor_enabled' => 'boolean',
        'must_change_password' => 'boolean',
        'failed_login_attempts' => 'integer',
    ];
}
