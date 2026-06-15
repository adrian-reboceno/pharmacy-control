<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PharmaControl\Catalog\Status\Infrastructure\Persistence\Eloquent\Model\EloquentStatus;
use PharmaControl\Catalog\Status\Infrastructure\Persistence\Mapper\StatusMapper;

final class EloquentStatusRepository implements StatusRepositoryContract
{
    public function __construct(private readonly StatusMapper $mapper) {}

    public function save(ProductStatus $status): void
    {
        EloquentStatus::updateOrCreate(
            ['id' => $status->getId()->value],
            $this->mapper->toPersistence($status)
        );
    }

    public function findById(StatusId $id): ?ProductStatus
    {
        $model = EloquentStatus::find($id->value);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByName(StatusName $name): ?ProductStatus
    {
        $model = EloquentStatus::whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)])->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByCode(StatusCode $code): ?ProductStatus
    {
        $model = EloquentStatus::whereRaw('LOWER(code) = ?', [mb_strtolower($code->value)])->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentStatus::query();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('name', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('code', 'ilike', '%'.$filters['search'].'%');
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
            'data' => array_map(fn ($m) => $this->mapper->toDomain($m), $paginator->items()),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
