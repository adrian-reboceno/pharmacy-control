<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\DeactivateRoute;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\RouteNotFoundException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;

final class DeactivateRouteUseCase
{
    public function __construct(
        private readonly RouteRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateRouteCommand $command): void
    {
        $route = $this->repository->findById(new RouteId($command->id));
        if ($route === null) {
            throw new RouteNotFoundException($command->id);
        }

        $route->deactivate();
        $this->repository->save($route);

        foreach ($route->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
