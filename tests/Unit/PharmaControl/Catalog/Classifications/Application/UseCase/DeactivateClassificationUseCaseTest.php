<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Classifications/Application/UseCase/DeactivateClassificationUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Classifications\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Classifications\Application\UseCase\DeactivateClassification\DeactivateClassificationCommand;
use PharmaControl\Catalog\Classifications\Application\UseCase\DeactivateClassification\DeactivateClassificationUseCase;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationDeactivated;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotFoundException;
use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateClassificationUseCaseTest extends TestCase
{
    private ClassificationRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private DeactivateClassificationUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ClassificationRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new DeactivateClassificationUseCase($this->repository, $this->events);
    }

    private function makeClassification(): MedicationClassification
    {
        return MedicationClassification::create(
            id: ClassificationId::generate(),
            lgsGroup: LgsGroup::V,
            name: new ClassificationName('OTC exclusivo farmacias'),
            prescriptionType: PrescriptionType::SIN_RECETA,
            validityDays: null,
            validityNote: null,
            createdBy: null,
        );
    }

    private function makeCommand(string $id): DeactivateClassificationCommand
    {
        return new DeactivateClassificationCommand($id, (string) UserId::generate());
    }

    public function test_deactivates_active_classification(): void
    {
        $c = $this->makeClassification();
        $this->repository->method('findById')->willReturn($c);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)($this->makeCommand($c->getId()->value));

        self::assertFalse($c->isActive());
    }

    public function test_throws_classification_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(ClassificationNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $c = $this->makeClassification();
        $c->deactivate();
        $c->releaseEvents();

        $this->repository->method('findById')->willReturn($c);

        $this->expectException(\DomainException::class);

        ($this->useCase)($this->makeCommand($c->getId()->value));
    }

    public function test_publishes_classification_deactivated_event(): void
    {
        $c = $this->makeClassification();
        $this->repository->method('findById')->willReturn($c);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(ClassificationDeactivated::class));

        ($this->useCase)($this->makeCommand($c->getId()->value));
    }
}
