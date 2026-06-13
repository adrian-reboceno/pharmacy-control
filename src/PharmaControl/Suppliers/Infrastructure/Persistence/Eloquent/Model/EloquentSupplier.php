<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentSupplier extends Model
{
    use HasUuids;

    protected $table = 'suppliers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'type', 'rfc', 'legal_name', 'trade_name',
        'address_street', 'address_ext_number', 'address_int_number',
        'address_neighborhood', 'address_municipality', 'address_state',
        'address_postal_code', 'address_country',
        'phone', 'email', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
