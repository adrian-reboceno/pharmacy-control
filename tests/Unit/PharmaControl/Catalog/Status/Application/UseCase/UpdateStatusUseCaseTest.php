<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Status\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Application\UseCase\UpdateStatus\UpdateStatusCommand;
use PharmaControl\Catalog\Status\Application\UseCase\UpdateStatus\UpdateStatusUseCase;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusCodeException;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusNameException;
use PharmaControl\Catalog\Status\Domain\Exception\StatusNotFoundException;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateStatusUseCaseTest extends TestCase
{
    private StatusRepositoryContract&MockObject $repository;
    private EventPublisherContract&MockObject $events;
    private UpdateStatusUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(StatusRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new UpdateStatusUseCase($this->repository, $this->events);
    }

    private function makeStatus(string $name = 'Activo', string $code = 'ACTIVO', ?string $id = null): ProductStatus
    {
        return ProductStatus::reconstitute(
            id: $id ? new StatusId($id) : StatusId::generate(),
            name: new StatusName($name),
            code: new StatusCode($code),
            description: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    private function command(array $overrides = []): UpdateStatusCommand
    {
        return new UpdateStatusCommand(
            id: $overrides['id'] ?? '00000000-0000-4000-8000-000000000001',
            name: $overrides['name'] ?? 'Activo',
            code: $overrides['code'] ?? 'ACTIVO',
            description: $overrides['description'] ?? null,
            actorUserId: '00000000-0000-4000-8000-000000000002',
        );
    }

    public function test_updates_status_and_returns_dto(): void
    {
        $existing = $this->makeStatus('Activo', 'ACTIVO', '00000000-0000-4000-8000-000000000001');

        $this->repository->method('findById')->willReturn($existing);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $dto = ($this->useCase)($this->command(['name' => 'Activo Mod', 'code' => 'ACTIVO_MOD']));

        $this->assertInstanceOf(StatusDTO::class, $dto);
        $this->assertSame('Activo Mod', $dto->name);
    }

    public function test_throws_status_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(StatusNotFoundException::class);

        ($this->useCase)($this->command());
    }

    public function test_throws_duplicate_status_name_exception_when_name_belongs_to_different_status(): void
    {
        $target  = $this->makeStatus('Activo', 'ACTIVO', '00000000-0000-4000-8000-000000000001');
        $another = $this->makeStatus('Descontinuado', 'DESCONTINUADO', '00000000-0000-4000-8000-000000000002');

        $this->repository->method('findById')->willReturn($target);
        $this->repository->method('findByName')->willReturn($another);

        $this->expectException(DuplicateStatusNameException::class);

        ($this->useCase)($this->command(['name' => 'Descontinuado']));
    }

    public function test_allows_keeping_same_name_on_own_status(): void
    {
        $target = $this->makeStatus('Activo', 'ACTIVO', '00000000-0000-4000-8000-000000000001');

        $this->repository->method('findById')->willReturn($target);
        $this->repository->method('findByName')->willReturn($target);
        $this->repository->method('findByCode')->willReturn(null);

        $dto = ($this->useCase)($this->command(['name' => 'Activo', 'code' => 'ACTIVO']));

        $this->assertSame('Activo', $dto->name);
    }

    public function test_throws_duplicate_status_code_exception_when_code_belongs_to_different_status(): void
    {
        $target  = $this->makeStatus('Activo', 'ACTIVO', '00000000-0000-4000-8000-000000000001');
        $another = $this->makeStatus('Descontinuado', 'DESCONTINUADO', '00000000-0000-4000-8000-000000000002');

        $this->repository->method('findById')->willReturn($target);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn($another);

        $this->expectException(DuplicateStatusCodeException::class);

        ($this->useCase)($this->command(['code' => 'DESCONTINUADO']));
    }

    public function test_allows_keeping_same_code_on_own_status(): void
    {
        $target = $this->makeStatus('Activo', 'ACTIVO', '00000000-0000-4000-8000-000000000001');

        $this->repository->method('findById')->willReturn($target);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn($target);

        $dto = ($this->useCase)($this->command(['name' => 'Activo', 'code' => 'ACTIVO']));

        $this->assertSame('ACTIVO', $dto->code);
    }

    public function test_publishes_status_updated_event(): void
    {
        $target = $this->makeStatus('Activo', 'ACTIVO', '00000000-0000-4000-8000-000000000001');

        $this->repository->method('findById')->willReturn($target);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('findByCode')->willReturn(null);

        $this->events->expects($this->once())->method('publish');

        ($this->useCase)($this->command(['name' => 'Activo Mod', 'code' => 'ACTIVO_MOD']));
    }
}
