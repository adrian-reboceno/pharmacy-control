<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Application/UseCase/DeactivateLaboratoryUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Application\UseCase\DeactivateLaboratory\DeactivateLaboratoryCommand;
use PharmaControl\Catalog\Laboratories\Application\UseCase\DeactivateLaboratory\DeactivateLaboratoryUseCase;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryDeactivated;
use PharmaControl\Catalog\Laboratories\Domain\Exception\LaboratoryNotFoundException;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateLaboratoryUseCaseTest extends TestCase
{
    private LaboratoryRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private DeactivateLaboratoryUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LaboratoryRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new DeactivateLaboratoryUseCase($this->repository, $this->events);
    }

    private function makeLaboratory(): Laboratory
    {
        return Laboratory::create(
            LaboratoryId::generate(),
            new LaboratoryName('Bayer'),
            new CountryCode('DE'),
            null,
            null,
        );
    }

    private function makeCommand(string $id): DeactivateLaboratoryCommand
    {
        return new DeactivateLaboratoryCommand($id, (string) UserId::generate());
    }

    public function test_deactivates_active_laboratory(): void
    {
        $lab = $this->makeLaboratory();

        $this->repository->method('findById')->willReturn($lab);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)($this->makeCommand($lab->getId()->value));

        self::assertFalse($lab->isActive());
    }

    public function test_throws_laboratory_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(LaboratoryNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $lab = $this->makeLaboratory();
        $lab->deactivate();
        $lab->releaseEvents();

        $this->repository->method('findById')->willReturn($lab);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('El laboratorio ya está inactivo.');

        ($this->useCase)($this->makeCommand($lab->getId()->value));
    }

    public function test_publishes_laboratory_deactivated_event(): void
    {
        $lab = $this->makeLaboratory();

        $this->repository->method('findById')->willReturn($lab);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(LaboratoryDeactivated::class));

        ($this->useCase)($this->makeCommand($lab->getId()->value));
    }
}
