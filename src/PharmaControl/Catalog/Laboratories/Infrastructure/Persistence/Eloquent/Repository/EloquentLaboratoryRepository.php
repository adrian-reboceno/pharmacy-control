<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Infrastructure/Persistence/Eloquent/Repository/EloquentLaboratoryRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Eloquent\Model\EloquentLaboratory;
use PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Mapper\LaboratoryMapper;

final class EloquentLaboratoryRepository implements LaboratoryRepositoryContract
{
    public function __construct(
        private readonly LaboratoryMapper $mapper,
    ) {}

    public function save(Laboratory $laboratory): void
    {
        EloquentLaboratory::updateOrCreate(
            ['id' => $laboratory->getId()->value],
            $this->mapper->toPersistence($laboratory),
        );
    }

    public function findById(LaboratoryId $id): ?Laboratory
    {
        $model = EloquentLaboratory::find($id->value);

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByName(LaboratoryName $name): ?Laboratory
    {
        $model = EloquentLaboratory::whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)])->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentLaboratory::query();

        if (! empty($filters['search'])) {
            $query->where('name', 'ilike', '%'.$filters['search'].'%');
        }
        if (isset($filters['country_code'])) {
            $query->where('country_code', strtoupper($filters['country_code']));
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);
        $paginator = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(
                fn (EloquentLaboratory $m) => $this->mapper->toDomain($m),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
