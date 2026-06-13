<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Application\UseCase\UpdateSupplier\UpdateSupplierCommand;
use PharmaControl\Suppliers\Application\UseCase\UpdateSupplier\UpdateSupplierUseCase;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Event\SupplierUpdated;
use PharmaControl\Suppliers\Domain\Exception\SupplierNotFoundException;
use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateSupplierUseCaseTest extends TestCase
{
    private SupplierRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private UpdateSupplierUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SupplierRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new UpdateSupplierUseCase($this->repository, $this->events);
    }

    private function makeExistingSupplier(): Supplier
    {
        $supplier = Supplier::create(
            id:        SupplierId::generate(),
            type:      SupplierType::MORAL,
            rfc:       new Rfc('ABC123456XYZ', SupplierType::MORAL),
            legalName: new LegalName('Distribuidora Original S.A.'),
            tradeName: null,
            address:   new Address('Av. Reforma', '1', null, 'Centro', 'Puebla', 'Puebla', '72000'),
            phone:     null,
            email:     null,
            createdBy: UserId::generate(),
        );
        $supplier->releaseEvents();

        return $supplier;
    }

    private function makeAddress(): array
    {
        return [
            'street'       => 'Calle Nueva',
            'ext_number'   => '456',
            'int_number'   => null,
            'neighborhood' => 'Norte',
            'municipality' => 'Puebla',
            'state'        => 'Puebla',
            'postal_code'  => '72010',
            'country'      => 'MX',
        ];
    }

    private function makeCommand(string $id, array $overrides = []): UpdateSupplierCommand
    {
        return new UpdateSupplierCommand(
            id:          $id,
            legalName:   $overrides['legalName']   ?? 'Distribuidora Actualizada S.A.',
            tradeName:   $overrides['tradeName']   ?? null,
            address:     $overrides['address']     ?? $this->makeAddress(),
            phone:       $overrides['phone']       ?? null,
            email:       $overrides['email']       ?? null,
            actorUserId: $overrides['actorUserId'] ?? (string) UserId::generate(),
        );
    }

    public function test_updates_supplier_and_returns_dto(): void
    {
        $supplier = $this->makeExistingSupplier();
        $this->repository->method('findById')->willReturn($supplier);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand((string) $supplier->id));

        self::assertInstanceOf(SupplierDTO::class, $result);
        self::assertSame('Distribuidora Actualizada S.A.', $result->legalName);
    }

    public function test_throws_supplier_not_found_when_id_does_not_exist(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(SupplierNotFoundException::class);

        ($this->useCase)($this->makeCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function test_publishes_supplier_updated_event(): void
    {
        $supplier = $this->makeExistingSupplier();
        $this->repository->method('findById')->willReturn($supplier);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SupplierUpdated::class));

        ($this->useCase)($this->makeCommand((string) $supplier->id));
    }

    public function test_type_and_rfc_remain_unchanged_after_update(): void
    {
        $supplier = $this->makeExistingSupplier();
        $this->repository->method('findById')->willReturn($supplier);
        $this->repository->method('save');

        ($this->useCase)($this->makeCommand((string) $supplier->id));

        self::assertSame(SupplierType::MORAL, $supplier->getType());
        self::assertSame('ABC123456XYZ', $supplier->getRfc()?->value);
    }
}
