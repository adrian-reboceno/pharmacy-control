<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Model/EloquentPasswordHistory.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

class EloquentPasswordHistory extends Model
{
    protected $table = 'password_histories';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'user_id', 'hash', 'created_at'];

    protected $hidden = ['hash'];

    protected $casts = [
        'created_at' => 'immutable_datetime',
    ];
}
