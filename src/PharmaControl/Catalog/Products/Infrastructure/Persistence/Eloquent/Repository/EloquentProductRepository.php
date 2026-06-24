<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Model\Product;
use PharmaControl\Catalog\Products\Domain\ValueObject\Barcode;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;
use PharmaControl\Catalog\Products\Infrastructure\Persistence\Eloquent\Model\EloquentProduct;
use PharmaControl\Catalog\Products\Infrastructure\Persistence\Mapper\ProductMapper;

final class EloquentProductRepository implements ProductRepositoryContract
{
    public function __construct(private readonly ProductMapper $mapper) {}

    public function save(Product $product): void
    {
        EloquentProduct::updateOrCreate(
            ['id' => $product->getId()->value],
            $this->mapper->toPersistence($product)
        );

        DB::table('product_active_ingredients')
            ->where('product_id', $product->getId()->value)
            ->delete();

        foreach ($product->getIngredients() as $ingredient) {
            DB::table('product_active_ingredients')->insert([
                'product_id' => $product->getId()->value,
                'ingredient_id' => $ingredient->ingredientId,
                'concentration' => $ingredient->concentration,
                'concentration_unit' => $ingredient->concentrationUnit,
            ]);
        }
    }

    public function findById(ProductId $id): ?Product
    {
        $model = EloquentProduct::with(['ingredients', 'images'])->find($id->value);

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByName(ProductName $name): ?Product
    {
        $model = EloquentProduct::with(['ingredients', 'images'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)])
            ->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByBarcode(Barcode $barcode): ?Product
    {
        $model = EloquentProduct::with(['ingredients', 'images'])
            ->where('barcode', $barcode->value)
            ->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentProduct::with(['ingredients', 'images']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('sanitary_reg', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['laboratory_id'])) {
            $query->where('laboratory_id', $filters['laboratory_id']);
        }

        if (! empty($filters['sale_condition'])) {
            $query->where('sale_condition', $filters['sale_condition']);
        }

        if (isset($filters['manage_lots'])) {
            $query->where('manage_lots', $filters['manage_lots']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $query->orderBy('name', 'asc');

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(
                fn (EloquentProduct $m) => $this->mapper->toDomain($m),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
