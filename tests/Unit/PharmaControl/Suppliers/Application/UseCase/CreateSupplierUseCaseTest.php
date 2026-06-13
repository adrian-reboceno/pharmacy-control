<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Application\UseCase\CreateSupplier\CreateSupplierCommand;
use PharmaControl\Suppliers\Application\UseCase\CreateSupplier\CreateSupplierUseCase;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Event\SupplierCreated;
use PharmaControl\Suppliers\Domain\Exception\DuplicateRfcException;
use PharmaControl\Suppliers\Domain\Exception\InvalidRfcException;
use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateSupplierUseCaseTest extends TestCase
{
    private SupplierRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private CreateSupplierUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SupplierRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new CreateSupplierUseCase($this->repository, $this->events);
    }

    private function makeAddress(): array
    {
        return [
            'street' => 'Av. Reforma',
            'ext_number' => '123',
            'int_number' => null,
            'neighborhood' => 'Centro',
            'municipality' => 'Puebla',
            'state' => 'Puebla',
            'postal_code' => '72000',
            'country' => 'MX',
        ];
    }

    private function makeCommand(array $overrides = []): CreateSupplierCommand
    {
        return new CreateSupplierCommand(
            type: $overrides['type'] ?? 'MORAL',
            rfc: array_key_exists('rfc', $overrides) ? $overrides['rfc'] : 'ABC123456XYZ',
            legalName: $overrides['legalName'] ?? 'Distribuidora Farmacéutica S.A.',
            tradeName: $overrides['tradeName'] ?? null,
            address: $overrides['address'] ?? $this->makeAddress(),
            phone: $overrides['phone'] ?? null,
            email: $overrides['email'] ?? null,
            actorUserId: $overrides['actorUserId'] ?? (string) UserId::generate(),
        );
    }

    public function test_creates_supplier_and_returns_dto_on_success(): void
    {
        $this->repository->method('findByRfc')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand());

        self::assertInstanceOf(SupplierDTO::class, $result);
        self::assertSame('MORAL', $result->type);
        self::assertSame('ABC123456XYZ', $result->rfc);
        self::assertSame('Distribuidora Farmacéutica S.A.', $result->legalName);
        self::assertTrue($result->isActive);
    }

    public function test_creates_supplier_without_rfc(): void
    {
        $this->repository->expects($this->never())->method('findByRfc');
        $this->repository->expects($this->once())->method('save');

        $result = ($this->useCase)($this->makeCommand(['rfc' => null]));

        self::assertNull($result->rfc);
    }

    public function test_throws_duplicate_rfc_exception_when_rfc_already_exists(): void
    {
        $existing = Supplier::create(
            id: SupplierId::generate(),
            type: SupplierType::MORAL,
            rfc: new Rfc('ABC123456XYZ', SupplierType::MORAL),
            legalName: new LegalName('Otro Proveedor S.A.'),
            tradeName: null,
            address: new Address('Calle', '1', null, 'Col', 'Mun', 'Puebla', '72000'),
            phone: null,
            email: null,
            createdBy: null,
        );

        $this->repository->method('findByRfc')->willReturn($existing);

        $this->expectException(DuplicateRfcException::class);

        ($this->useCase)($this->makeCommand());
    }

    public function test_publishes_supplier_created_event_on_success(): void
    {
        $this->repository->method('findByRfc')->willReturn(null);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SupplierCreated::class));

        ($this->useCase)($this->makeCommand());
    }

    public function test_throws_invalid_rfc_exception_for_wrong_length(): void
    {
        $this->expectException(InvalidRfcException::class);

        ($this->useCase)($this->makeCommand(['rfc' => 'TOOSHORT']));
    }

    public function test_throws_invalid_argument_exception_for_empty_legal_name(): void
    {
        $this->repository->method('findByRfc')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);

        ($this->useCase)($this->makeCommand(['legalName' => '']));
    }

    public function test_throws_invalid_argument_exception_for_invalid_state(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->useCase)($this->makeCommand([
            'address' => array_merge($this->makeAddress(), ['state' => 'EstadoFalso']),
        ]));
    }
}
