<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Status\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Status\Application\UseCase\DeactivateStatus\DeactivateStatusCommand;
use PharmaControl\Catalog\Status\Application\UseCase\DeactivateStatus\DeactivateStatusUseCase;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Exception\StatusNotFoundException;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateStatusUseCaseTest extends TestCase
{
    private StatusRepositoryContract&MockObject $repository;
    private EventPublisherContract&MockObject $events;
    private DeactivateStatusUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StatusRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new DeactivateStatusUseCase($this->repository, $this->events);
    }

    private function makeStatus(bool $isActive = true): ProductStatus
    {
        return ProductStatus::reconstitute(
            id: StatusId::generate(),
            name: new StatusName('Activo'),
            code: new StatusCode('ACTIVO'),
            description: null,
            isActive: $isActive,
            createdBy: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    private function command(): DeactivateStatusCommand
    {
        return new DeactivateStatusCommand(
            id: '00000000-0000-4000-8000-000000000001',
            actorUserId: '00000000-0000-4000-8000-000000000002',
        );
    }

    public function test_deactivates_active_status(): void
    {
        $status = $this->makeStatus(true);

        $this->repository->method('findById')->willReturn($status);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)($this->command());

        $this->assertFalse($status->isActive());
    }

    public function test_throws_status_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(StatusNotFoundException::class);

        ($this->useCase)($this->command());
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $status = $this->makeStatus(false);

        $this->repository->method('findById')->willReturn($status);

        $this->expectException(\DomainException::class);

        ($this->useCase)($this->command());
    }

    public function test_publishes_status_deactivated_event(): void
    {
        $status = $this->makeStatus(true);

        $this->repository->method('findById')->willReturn($status);

        $this->events->expects($this->once())->method('publish');

        ($this->useCase)($this->command());
    }
}
