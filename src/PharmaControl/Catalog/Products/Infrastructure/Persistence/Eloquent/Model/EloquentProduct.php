<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Eloquent\Model\EloquentIngredient;

class EloquentProduct extends Model
{
    use HasUuids;

    protected $table = 'products';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'type',
        'name',
        'description',
        'sale_condition',
        'sanitary_reg',
        'barcode',
        'status_id',
        'category_id',
        'laboratory_id',
        'unit_id',
        'presentation_id',
        'route_id',
        'location_id',
        'units_per_box',
        'units_per_blister',
        'min_stock',
        'max_stock',
        'expiry_alert_days',
        'manage_lots',
        'allow_fraction',
        'retail_margin',
        'wholesale_margin',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'manage_lots' => 'boolean',
        'allow_fraction' => 'boolean',
        'retail_margin' => 'float',
        'wholesale_margin' => 'float',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(
            EloquentIngredient::class,
            'product_active_ingredients',
            'product_id',
            'ingredient_id'
        )->withPivot('concentration', 'concentration_unit');
    }

    public function images(): HasMany
    {
        return $this->hasMany(EloquentProductImage::class, 'product_id')
            ->orderBy('sort_order');
    }
}
