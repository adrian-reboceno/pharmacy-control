<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Eloquent\Model\EloquentUnit;
use PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Mapper\UnitMapper;

final class EloquentUnitRepository implements UnitRepositoryContract
{
    public function __construct(private readonly UnitMapper $mapper) {}

    public function save(UnitOfMeasurement $unit): void
    {
        EloquentUnit::updateOrCreate(
            ['id' => $unit->getId()->value],
            $this->mapper->toPersistence($unit),
        );
    }

    public function findById(UnitId $id): ?UnitOfMeasurement
    {
        $model = EloquentUnit::find($id->value);

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByName(UnitName $name): ?UnitOfMeasurement
    {
        $model = EloquentUnit::whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)])->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findBySymbol(UnitSymbol $symbol): ?UnitOfMeasurement
    {
        $model = EloquentUnit::where('symbol', $symbol->value)->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentUnit::query();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('name', 'ilike', '%'.$filters['search'].'%')
                  ->orWhere('symbol', 'ilike', '%'.$filters['search'].'%');
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $query->orderByRaw("CASE type WHEN 'CONCENTRATION' THEN 1 ELSE 2 END, name ASC");

        $perPage   = min((int) ($filters['per_page'] ?? 20), 100);
        $page      = max((int) ($filters['page'] ?? 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data'         => array_map(
                fn (EloquentUnit $m) => $this->mapper->toDomain($m),
                $paginator->items(),
            ),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }
}
