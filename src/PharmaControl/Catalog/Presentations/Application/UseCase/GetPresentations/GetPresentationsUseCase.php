<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\GetPresentations;

use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;

final class GetPresentationsUseCase
{
    public function __construct(
        private readonly PresentationRepositoryContract $repository,
    ) {}

    public function __invoke(GetPresentationsQuery $query): array
    {
        $filters = [];

        if ($query->search !== null) {
            $filters['search'] = $query->search;
        }
        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }

        $filters['per_page'] = $query->perPage;
        $filters['page'] = $query->page;

        $result = $this->repository->findAll($filters);

        return [
            ...$result,
            'data' => array_map(
                fn ($presentation) => PresentationDTO::fromDomain($presentation),
                $result['data']
            ),
        ];
    }
}
