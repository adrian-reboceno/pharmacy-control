<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Classifications/Application/UseCase/UpdateClassificationUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Classifications\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;
use PharmaControl\Catalog\Classifications\Application\UseCase\UpdateClassification\UpdateClassificationCommand;
use PharmaControl\Catalog\Classifications\Application\UseCase\UpdateClassification\UpdateClassificationUseCase;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationUpdated;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotFoundException;
use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateClassificationUseCaseTest extends TestCase
{
    private ClassificationRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private UpdateClassificationUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ClassificationRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new UpdateClassificationUseCase($this->repository, $this->events);
    }

    private function makeClassification(): MedicationClassification
    {
        return MedicationClassification::create(
            id: ClassificationId::generate(),
            lgsGroup: LgsGroup::II,
            name: new ClassificationName('Psicotrópicos II'),
            prescriptionType: PrescriptionType::NORMAL,
            validityDays: 30,
            validityNote: null,
            createdBy: null,
        );
    }

    private function makeCommand(string $id): UpdateClassificationCommand
    {
        return new UpdateClassificationCommand(
            id: $id,
            name: 'Psicotrópicos Grupo II',
            prescriptionType: 'NORMAL',
            validityDays: 60,
            validityNote: 'Nota actualizada',
            actorUserId: (string) UserId::generate(),
        );
    }

    public function test_updates_classification_and_returns_dto(): void
    {
        $c = $this->makeClassification();

        $this->repository->method('findById')->willReturn($c);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand($c->getId()->value));

        self::assertInstanceOf(ClassificationDTO::class, $result);
        self::assertSame('Psicotrópicos Grupo II', $result->name);
    }

    public function test_throws_classification_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(ClassificationNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_throws_invalid_argument_exception_for_invalid_prescription_type(): void
    {
        $c = $this->makeClassification();
        $this->repository->method('findById')->willReturn($c);

        $this->expectException(\InvalidArgumentException::class);

        ($this->useCase)(new UpdateClassificationCommand(
            id: $c->getId()->value,
            name: 'Test',
            prescriptionType: 'RECETA_MAGICA',
            validityDays: null,
            validityNote: null,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_publishes_classification_updated_event(): void
    {
        $c = $this->makeClassification();

        $this->repository->method('findById')->willReturn($c);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(ClassificationUpdated::class));

        ($this->useCase)($this->makeCommand($c->getId()->value));
    }
}
