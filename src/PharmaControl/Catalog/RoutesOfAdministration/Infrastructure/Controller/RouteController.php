<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Controller;

use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\CreateRoute\CreateRouteCommand;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\CreateRoute\CreateRouteUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\DeactivateRoute\DeactivateRouteCommand;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\DeactivateRoute\DeactivateRouteUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\GetRoutes\GetRoutesQuery;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\GetRoutes\GetRoutesUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\UpdateRoute\UpdateRouteCommand;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\UpdateRoute\UpdateRouteUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\RouteNotFoundException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;

final class RouteController
{
    public function __construct(
        private readonly CreateRouteUseCase $create,
        private readonly UpdateRouteUseCase $update,
        private readonly DeactivateRouteUseCase $deactivate,
        private readonly GetRoutesUseCase $get,
        private readonly RouteRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetRoutesQuery(
            search: $data['search'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage: (int) ($data['per_page'] ?? 20),
            page: (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): RouteDTO
    {
        return ($this->create)(new CreateRouteCommand(
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): RouteDTO
    {
        $route = $this->repository->findById(new RouteId($id));
        if ($route === null) {
            throw new RouteNotFoundException($id);
        }

        return RouteDTO::fromDomain($route);
    }

    public function update(string $id, array $data): RouteDTO
    {
        return ($this->update)(new UpdateRouteCommand(
            id: $id,
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateRouteCommand($id, $actorUserId));
    }
}
