<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\CreateRoute\CreateRouteCommand;
use PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\CreateRoute\CreateRouteUseCase;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteCreated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteCodeException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteNameException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateRouteUseCaseTest extends TestCase
{
    private RouteRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private CreateRouteUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RouteRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new CreateRouteUseCase($this->repository, $this->events);
    }

    private function makeCommand(string $name = 'Oral', string $code = 'VO'): CreateRouteCommand
    {
        return new CreateRouteCommand(
            name: $name,
            code: $code,
            description: 'Administración por la boca.',
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_creates_route_and_returns_dto_on_success(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand());

        self::assertInstanceOf(RouteDTO::class, $result);
        self::assertSame('Oral', $result->name);
        self::assertSame('VO', $result->code);
        self::assertTrue($result->isActive);
    }

    public function test_throws_duplicate_route_name_exception_when_name_already_exists_case_insensitive(): void
    {
        $existing = RouteOfAdministration::create(
            RouteId::generate(),
            new RouteName('ORAL'),
            new RouteCode('VO2'),
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn($existing);
        $this->repository->method('findByCode')->willReturn(null);

        $this->expectException(DuplicateRouteNameException::class);

        ($this->useCase)($this->makeCommand('Oral'));
    }

    public function test_throws_duplicate_route_code_exception_when_code_already_exists_case_insensitive(): void
    {
        $existing = RouteOfAdministration::create(
            RouteId::generate(),
            new RouteName('Oral Alternativa'),
            new RouteCode('VO'),
            null,
            null,
        );

        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn($existing);

        $this->expectException(DuplicateRouteCodeException::class);

        ($this->useCase)($this->makeCommand('Oral', 'VO'));
    }

    public function test_publishes_route_created_event(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RouteCreated::class));

        ($this->useCase)($this->makeCommand());
    }
}
