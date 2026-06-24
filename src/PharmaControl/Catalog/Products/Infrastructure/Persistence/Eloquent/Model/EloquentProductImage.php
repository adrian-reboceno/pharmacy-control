<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EloquentProductImage extends Model
{
    use HasUuids;

    protected $table = 'product_images';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'product_id',
        'url',
        'filename',
        'mime_type',
        'size_bytes',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'size_bytes' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
