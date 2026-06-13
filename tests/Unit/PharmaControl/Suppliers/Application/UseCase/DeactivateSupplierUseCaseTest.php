<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Suppliers\Application\UseCase\DeactivateSupplier\DeactivateSupplierCommand;
use PharmaControl\Suppliers\Application\UseCase\DeactivateSupplier\DeactivateSupplierUseCase;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Event\SupplierDeactivated;
use PharmaControl\Suppliers\Domain\Exception\SupplierNotFoundException;
use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateSupplierUseCaseTest extends TestCase
{
    private SupplierRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private DeactivateSupplierUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SupplierRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new DeactivateSupplierUseCase($this->repository, $this->events);
    }

    private function makeActiveSupplier(): Supplier
    {
        $supplier = Supplier::create(
            id:        SupplierId::generate(),
            type:      SupplierType::MORAL,
            rfc:       new Rfc('ABC123456XYZ', SupplierType::MORAL),
            legalName: new LegalName('Distribuidora S.A.'),
            tradeName: null,
            address:   new Address('Av. Reforma', '1', null, 'Centro', 'Puebla', 'Puebla', '72000'),
            phone:     null,
            email:     null,
            createdBy: UserId::generate(),
        );
        $supplier->releaseEvents();

        return $supplier;
    }

    public function test_deactivates_supplier_and_saves(): void
    {
        $supplier = $this->makeActiveSupplier();
        $this->repository->method('findById')->willReturn($supplier);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)(new DeactivateSupplierCommand(
            id:          (string) $supplier->id,
            actorUserId: (string) UserId::generate(),
        ));

        self::assertFalse($supplier->isActive());
    }

    public function test_throws_supplier_not_found_when_id_does_not_exist(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(SupplierNotFoundException::class);

        ($this->useCase)(new DeactivateSupplierCommand(
            id:          '00000000-0000-4000-8000-000000000000',
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_publishes_supplier_deactivated_event(): void
    {
        $supplier = $this->makeActiveSupplier();
        $this->repository->method('findById')->willReturn($supplier);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SupplierDeactivated::class));

        ($this->useCase)(new DeactivateSupplierCommand(
            id:          (string) $supplier->id,
            actorUserId: (string) UserId::generate(),
        ));
    }

    public function test_throws_domain_exception_when_supplier_is_already_inactive(): void
    {
        $supplier = $this->makeActiveSupplier();
        $supplier->deactivate();
        $supplier->releaseEvents();

        $this->repository->method('findById')->willReturn($supplier);

        $this->expectException(\DomainException::class);

        ($this->useCase)(new DeactivateSupplierCommand(
            id:          (string) $supplier->id,
            actorUserId: (string) UserId::generate(),
        ));
    }
}
