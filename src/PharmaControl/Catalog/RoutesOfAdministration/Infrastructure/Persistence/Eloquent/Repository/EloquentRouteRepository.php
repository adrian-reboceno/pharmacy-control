<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Eloquent\Model\EloquentRoute;
use PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Mapper\RouteMapper;

final class EloquentRouteRepository implements RouteRepositoryContract
{
    public function __construct(private readonly RouteMapper $mapper) {}

    public function save(RouteOfAdministration $route): void
    {
        EloquentRoute::updateOrCreate(
            ['id' => $route->getId()->value],
            $this->mapper->toPersistence($route)
        );
    }

    public function findById(RouteId $id): ?RouteOfAdministration
    {
        $model = EloquentRoute::find($id->value);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByName(RouteName $name): ?RouteOfAdministration
    {
        $model = EloquentRoute::whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)])->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByCode(RouteCode $code): ?RouteOfAdministration
    {
        $model = EloquentRoute::whereRaw('LOWER(code) = ?', [mb_strtolower($code->value)])->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentRoute::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('name', 'ilike', '%' . $filters['search'] . '%')
                  ->orWhere('code', 'ilike', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $query->orderBy('name', 'asc');

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
