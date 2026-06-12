<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\DeactivateRoute\DeactivateRouteCommand;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\DeactivateRoute\DeactivateRouteUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteDeactivated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\RouteNotFoundException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateRouteUseCaseTest extends TestCase
{
    private RouteRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private DeactivateRouteUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RouteRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new DeactivateRouteUseCase($this->repository, $this->events);
    }

    private function makeRoute(): RouteOfAdministration
    {
        return RouteOfAdministration::create(
            RouteId::generate(),
            new RouteName('Oral'),
            new RouteCode('VO'),
            null,
            null,
        );
    }

    private function makeCommand(string $id): DeactivateRouteCommand
    {
        return new DeactivateRouteCommand($id, (string) UserId::generate());
    }

    public function test_deactivates_active_route(): void
    {
        $route = $this->makeRoute();
        $this->repository->method('findById')->willReturn($route);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)($this->makeCommand($route->getId()->value));

        self::assertFalse($route->isActive());
    }

    public function test_throws_route_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(RouteNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $route = $this->makeRoute();
        $route->releaseEvents();
        $route->deactivate();
        $route->releaseEvents();

        $this->repository->method('findById')->willReturn($route);

        $this->expectException(\DomainException::class);

        ($this->useCase)($this->makeCommand($route->getId()->value));
    }

    public function test_publishes_route_deactivated_event(): void
    {
        $route = $this->makeRoute();
        $this->repository->method('findById')->willReturn($route);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RouteDeactivated::class));

        ($this->useCase)($this->makeCommand($route->getId()->value));
    }
}
