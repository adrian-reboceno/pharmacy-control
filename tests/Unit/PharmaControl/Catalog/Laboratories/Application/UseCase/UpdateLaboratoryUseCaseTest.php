<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Application/UseCase/UpdateLaboratoryUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Application\UseCase\UpdateLaboratory\UpdateLaboratoryCommand;
use PharmaControl\Catalog\Laboratories\Application\UseCase\UpdateLaboratory\UpdateLaboratoryUseCase;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryUpdated;
use PharmaControl\Catalog\Laboratories\Domain\Exception\DuplicateLaboratoryNameException;
use PharmaControl\Catalog\Laboratories\Domain\Exception\LaboratoryNotFoundException;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateLaboratoryUseCaseTest extends TestCase
{
    private LaboratoryRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private UpdateLaboratoryUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LaboratoryRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new UpdateLaboratoryUseCase($this->repository, $this->events);
    }

    private function makeLaboratory(string $name = 'Bayer'): Laboratory
    {
        return Laboratory::create(
            LaboratoryId::generate(),
            new LaboratoryName($name),
            new CountryCode('DE'),
            null,
            null,
        );
    }

    private function makeCommand(string $id, string $name = 'Bayer AG'): UpdateLaboratoryCommand
    {
        return new UpdateLaboratoryCommand(
            id: $id,
            name: $name,
            countryCode: 'CH',
            website: null,
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_updates_laboratory_and_returns_dto_on_success(): void
    {
        $lab = $this->makeLaboratory();

        $this->repository->method('findById')->willReturn($lab);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($lab->getId()->value));

        self::assertInstanceOf(LaboratoryDTO::class, $result);
        self::assertSame('Bayer AG', $result->name);
        self::assertSame('CH', $result->countryCode);
    }

    public function test_throws_laboratory_not_found_exception_when_id_does_not_exist(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(LaboratoryNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_duplicate_laboratory_name_exception_when_new_name_belongs_to_different_laboratory(): void
    {
        $lab = $this->makeLaboratory('Bayer');
        $other = $this->makeLaboratory('Pfizer');

        $this->repository->method('findById')->willReturn($lab);
        $this->repository->method('findByName')->willReturn($other);

        $this->expectException(DuplicateLaboratoryNameException::class);

        ($this->useCase)($this->makeCommand($lab->getId()->value, 'Pfizer'));
    }

    public function test_allows_keeping_same_name_no_duplicate_on_own_id(): void
    {
        $lab = $this->makeLaboratory('Bayer');

        $this->repository->method('findById')->willReturn($lab);
        $this->repository->method('findByName')->willReturn($lab);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($lab->getId()->value, 'Bayer'));

        self::assertSame('Bayer', $result->name);
    }

    public function test_publishes_laboratory_updated_event(): void
    {
        $lab = $this->makeLaboratory();

        $this->repository->method('findById')->willReturn($lab);
        $this->repository->method('findByName')->willReturn(null);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(LaboratoryUpdated::class));

        ($this->useCase)($this->makeCommand($lab->getId()->value));
    }
}
