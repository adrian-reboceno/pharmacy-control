<?php
declare(strict_types=1);
 
namespace PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\GetUnits;
 
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
 
final class GetUnitsUseCase
{
    public function __construct(
        private readonly UnitRepositoryContract $repository,
    ) {}
 
    public function __invoke(GetUnitsQuery $query): array
    {
        $filters = [];
 
        if ($query->type !== null) {
            $filters['type'] = $query->type;
        }
        if ($query->search !== null) {
            $filters['search'] = $query->search;
        }
        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }
 
        $filters['per_page'] = $query->perPage;
        $filters['page']     = $query->page;
 
        $result = $this->repository->findAll($filters);
 
        // Mapear UnitOfMeasurement[] → UnitDTO[] antes de retornar a Infrastructure
        return [
            ...$result,
            'data' => array_map(
                fn($unit) => UnitDTO::fromDomain($unit),
                $result['data']
            ),
        ];
    }
}