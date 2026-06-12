<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Eloquent\Model\EloquentPresentation;
use PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Mapper\PresentationMapper;

final class EloquentPresentationRepository implements PresentationRepositoryContract
{
    public function __construct(private readonly PresentationMapper $mapper) {}

    public function save(Presentation $presentation): void
    {
        EloquentPresentation::updateOrCreate(
            ['id' => $presentation->getId()->value],
            $this->mapper->toPersistence($presentation)
        );
    }

    public function findById(PresentationId $id): ?Presentation
    {
        $model = EloquentPresentation::find($id->value);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByName(PresentationName $name): ?Presentation
    {
        $model = EloquentPresentation::whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)])->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByAbbreviation(Abbreviation $abbreviation): ?Presentation
    {
        $model = EloquentPresentation::whereRaw('LOWER(abbreviation) = ?', [mb_strtolower($abbreviation->value)])->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findAll(array $filters = []): array
    {
        $query = EloquentPresentation::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('name', 'ilike', '%' . $filters['search'] . '%')
                  ->orWhere('abbreviation', 'ilike', '%' . $filters['search'] . '%');
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
