<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\CreateRoute;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteCodeException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteNameException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;

final class CreateRouteUseCase
{
    public function __construct(
        private readonly RouteRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(CreateRouteCommand $command): RouteDTO
    {
        $name = new RouteName($command->name);
        $code = new RouteCode($command->code);

        if ($this->repository->findByName($name) !== null) {
            throw new DuplicateRouteNameException($command->name);
        }

        if ($this->repository->findByCode($code) !== null) {
            throw new DuplicateRouteCodeException($command->code);
        }

        if ($command->description !== null && mb_strlen($command->description) > 500) {
            throw new \InvalidArgumentException('La descripción no puede exceder 500 caracteres.');
        }

        $route = RouteOfAdministration::create(
            RouteId::generate(),
            $name,
            $code,
            $command->description,
            new UserId($command->actorUserId),
        );

        $this->repository->save($route);

        foreach ($route->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return RouteDTO::fromDomain($route);
    }
}
