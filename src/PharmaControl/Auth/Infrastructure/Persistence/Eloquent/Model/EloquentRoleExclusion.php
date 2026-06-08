<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Model/EloquentRoleExclusion.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

class EloquentRoleExclusion extends Model
{
    protected $table = 'role_exclusions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'role_a_id', 'role_b_id', 'level', 'reason', 'created_by', 'created_at'];

    protected $casts = [
        'created_at' => 'immutable_datetime',
    ];
}
