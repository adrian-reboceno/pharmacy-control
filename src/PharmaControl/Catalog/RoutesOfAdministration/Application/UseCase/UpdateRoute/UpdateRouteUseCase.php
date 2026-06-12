<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\UpdateRoute;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteCodeException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteNameException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\RouteNotFoundException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;

final class UpdateRouteUseCase
{
    public function __construct(
        private readonly RouteRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(UpdateRouteCommand $command): RouteDTO
    {
        $route = $this->repository->findById(new RouteId($command->id));
        if ($route === null) {
            throw new RouteNotFoundException($command->id);
        }

        $name = new RouteName($command->name);
        $code = new RouteCode($command->code);

        $existingByName = $this->repository->findByName($name);
        if ($existingByName !== null && ! $existingByName->getId()->equals($route->getId())) {
            throw new DuplicateRouteNameException($command->name);
        }

        $existingByCode = $this->repository->findByCode($code);
        if ($existingByCode !== null && ! $existingByCode->getId()->equals($route->getId())) {
            throw new DuplicateRouteCodeException($command->code);
        }

        $route->update($name, $code, $command->description);
        $this->repository->save($route);

        foreach ($route->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return RouteDTO::fromDomain($route);
    }
}
