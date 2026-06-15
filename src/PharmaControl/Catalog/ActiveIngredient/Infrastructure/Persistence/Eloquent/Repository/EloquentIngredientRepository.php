<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Eloquent\Model\EloquentIngredient;
use PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Mapper\IngredientMapper;

final class EloquentIngredientRepository implements IngredientRepositoryContract
{
    public function __construct(private readonly IngredientMapper $mapper) {}

    public function save(ActiveIngredient $ingredient): void
    {
        EloquentIngredient::updateOrCreate(
            ['id' => $ingredient->getId()->value],
            $this->mapper->toPersistence($ingredient)
        );
    }

    public function findById(IngredientId $id): ?ActiveIngredient
    {
        $model = EloquentIngredient::find($id->value);

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByName(IngredientName $name): ?ActiveIngredient
    {
        $model = EloquentIngredient::whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)])->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByDciCode(DciCode $code): ?ActiveIngredient
    {
        $model = EloquentIngredient::whereRaw('LOWER(dci_code) = ?', [mb_strtolower($code->value)])->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByCasNumber(CasNumber $cas): ?ActiveIngredient
    {
        $model = EloquentIngredient::where('cas_number', $cas->value)->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentIngredient::query();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('name', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('dci_code', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('cas_number', 'ilike', '%'.$filters['search'].'%');
            });
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
                fn (EloquentIngredient $m) => $this->mapper->toDomain($m),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
