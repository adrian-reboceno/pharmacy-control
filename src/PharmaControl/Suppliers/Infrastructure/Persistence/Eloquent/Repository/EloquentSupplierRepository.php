<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Infrastructure\Persistence\Eloquent\Model\EloquentSupplier;
use PharmaControl\Suppliers\Infrastructure\Persistence\Mapper\SupplierMapper;

final class EloquentSupplierRepository implements SupplierRepositoryContract
{
    public function __construct(private readonly SupplierMapper $mapper) {}

    public function save(Supplier $supplier): void
    {
        EloquentSupplier::updateOrCreate(
            ['id' => $supplier->getId()->value],
            $this->mapper->toPersistence($supplier)
        );
    }

    public function findById(SupplierId $id): ?Supplier
    {
        $model = EloquentSupplier::find($id->value);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByRfc(Rfc $rfc): ?Supplier
    {
        $model = EloquentSupplier::where('rfc', $rfc->value)->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentSupplier::query();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('legal_name', 'ilike', '%' . $filters['search'] . '%')
                    ->orWhere('trade_name', 'ilike', '%' . $filters['search'] . '%')
                    ->orWhere('rfc', 'ilike', '%' . $filters['search'] . '%');
            });
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $query->orderBy('legal_name', 'asc');

        $perPage   = min((int) ($filters['per_page'] ?? 20), 100);
        $page      = max((int) ($filters['page'] ?? 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data'         => array_map(fn ($m) => $this->mapper->toDomain($m), $paginator->items()),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }
}
