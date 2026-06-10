<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Infrastructure/Persistence/Eloquent/Repository/EloquentClassificationRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Eloquent\Model\EloquentClassification;
use PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Mapper\ClassificationMapper;

final class EloquentClassificationRepository implements ClassificationRepositoryContract
{
    public function __construct(
        private readonly ClassificationMapper $mapper,
    ) {}

    public function save(MedicationClassification $c): void
    {
        EloquentClassification::updateOrCreate(
            ['id' => $c->getId()->value],
            $this->mapper->toPersistence($c)
        );
    }

    public function findById(ClassificationId $id): ?MedicationClassification
    {
        $model = EloquentClassification::find($id->value);

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByLgsGroup(LgsGroup $group): ?MedicationClassification
    {
        $model = EloquentClassification::where('lgs_group', $group->value)->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentClassification::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_controlled'])) {
            $controlledGroups = ['I', 'II', 'III', 'IV_A', 'IV_B'];
            if ($filters['is_controlled']) {
                $query->whereIn('lgs_group', $controlledGroups);
            } else {
                $query->whereNotIn('lgs_group', $controlledGroups);
            }
        }

        $query->orderByRaw("CASE lgs_group
            WHEN 'I'    THEN 1
            WHEN 'II'   THEN 2
            WHEN 'III'  THEN 3
            WHEN 'IV_A' THEN 4
            WHEN 'IV_B' THEN 5
            WHEN 'V'    THEN 6
            WHEN 'VI'   THEN 7
        END");

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(
                fn (EloquentClassification $m) => $this->mapper->toDomain($m),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
