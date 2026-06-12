<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\UpdateRoute\UpdateRouteCommand;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\UpdateRoute\UpdateRouteUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteUpdated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteCodeException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteNameException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\RouteNotFoundException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateRouteUseCaseTest extends TestCase
{
    private RouteRepositoryContract&MockObject $repository;
    private EventPublisherContract&MockObject  $events;
    private UpdateRouteUseCase                 $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RouteRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new UpdateRouteUseCase($this->repository, $this->events);
    }

    private function makeRoute(string $name = 'Oral', string $code = 'VO'): RouteOfAdministration
    {
        return RouteOfAdministration::create(
            RouteId::generate(),
            new RouteName($name),
            new RouteCode($code),
            null,
            null,
        );
    }

    private function makeCommand(string $id, string $name = 'Sublingual', string $code = 'SL'): UpdateRouteCommand
    {
        return new UpdateRouteCommand(
            id:          $id,
            name:        $name,
            code:        $code,
            description: null,
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_updates_route_and_returns_dto(): void
    {
        $existing = $this->makeRoute();
        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($existing->getId()->value));

        self::assertInstanceOf(RouteDTO::class, $result);
        self::assertSame('Sublingual', $result->name);
        self::assertSame('SL', $result->code);
    }

    public function test_throws_route_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(RouteNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_duplicate_route_name_exception_when_name_belongs_to_different_route(): void
    {
        $existing = $this->makeRoute('Oral', 'VO');
        $other    = $this->makeRoute('Sublingual', 'SL');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn($other);
        $this->repository->method('findByCode')->willReturn(null);

        $this->expectException(DuplicateRouteNameException::class);

        ($this->useCase)($this->makeCommand($existing->getId()->value, 'Sublingual', 'VO'));
    }

    public function test_allows_keeping_same_name_on_own_route(): void
    {
        $existing = $this->makeRoute('Oral', 'VO');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn($existing);
        $this->repository->method('findByCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($existing->getId()->value, 'Oral', 'VO'));

        self::assertSame('Oral', $result->name);
    }

    public function test_throws_duplicate_route_code_exception_when_code_belongs_to_different_route(): void
    {
        $existing = $this->makeRoute('Oral', 'VO');
        $other    = $this->makeRoute('Sublingual', 'SL');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn($other);

        $this->expectException(DuplicateRouteCodeException::class);

        ($this->useCase)($this->makeCommand($existing->getId()->value, 'Oral Nueva', 'SL'));
    }

    public function test_allows_keeping_same_code_on_own_route(): void
    {
        $existing = $this->makeRoute('Oral', 'VO');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn($existing);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($existing->getId()->value, 'Oral Nueva', 'VO'));

        self::assertSame('VO', $result->code);
    }

    public function test_publishes_route_updated_event(): void
    {
        $existing = $this->makeRoute();

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RouteUpdated::class));

        ($this->useCase)($this->makeCommand($existing->getId()->value));
    }
}
