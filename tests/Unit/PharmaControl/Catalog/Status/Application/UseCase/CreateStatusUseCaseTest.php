<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Status\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Application\UseCase\CreateStatus\CreateStatusCommand;
use PharmaControl\Catalog\Status\Application\UseCase\CreateStatus\CreateStatusUseCase;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusCodeException;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusNameException;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateStatusUseCaseTest extends TestCase
{
    private StatusRepositoryContract&MockObject $repository;
    private EventPublisherContract&MockObject $events;
    private CreateStatusUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StatusRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new CreateStatusUseCase($this->repository, $this->events);
    }

    private function command(array $overrides = []): CreateStatusCommand
    {
        return new CreateStatusCommand(
            name: $overrides['name'] ?? 'En revisión',
            code: $overrides['code'] ?? 'EN_REVISION',
            description: $overrides['description'] ?? null,
            actorUserId: $overrides['actorUserId'] ?? '00000000-0000-4000-8000-000000000001',
        );
    }

    public function test_creates_status_and_returns_dto_on_success(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $dto = ($this->useCase)($this->command());

        $this->assertInstanceOf(StatusDTO::class, $dto);
        $this->assertSame('EN_REVISION', $dto->code);
    }

    public function test_throws_duplicate_status_name_exception_when_name_already_exists_case_insensitive(): void
    {
        $existing = ProductStatus::reconstitute(
            StatusId::generate(),
            new StatusName('En revisión'),
            new StatusCode('EN_REVISION'),
            null,
            true,
            null,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
        );

        $this->repository->method('findByName')->willReturn($existing);

        $this->expectException(DuplicateStatusNameException::class);

        ($this->useCase)($this->command(['name' => 'en revisión']));
    }

    public function test_throws_duplicate_status_code_exception_when_code_already_exists_case_insensitive(): void
    {
        $existing = ProductStatus::reconstitute(
            StatusId::generate(),
            new StatusName('Activo'),
            new StatusCode('ACTIVO'),
            null,
            true,
            null,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
        );

        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn($existing);

        $this->expectException(DuplicateStatusCodeException::class);

        ($this->useCase)($this->command(['code' => 'activo']));
    }

    public function test_publishes_status_created_event(): void
    {
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);

        $this->events->expects($this->once())->method('publish');

        ($this->useCase)($this->command());
    }
}
