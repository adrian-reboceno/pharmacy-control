<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Suppliers\Domain\Event\SupplierCreated;
use PharmaControl\Suppliers\Domain\Event\SupplierDeactivated;
use PharmaControl\Suppliers\Domain\Event\SupplierUpdated;
use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\Email;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Phone;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;
use PHPUnit\Framework\TestCase;

final class SupplierTest extends TestCase
{
    private function makeAddress(): Address
    {
        return new Address(
            street:       'Av. Reforma',
            extNumber:    '123',
            intNumber:    null,
            neighborhood: 'Centro',
            municipality: 'Puebla',
            state:        'Puebla',
            postalCode:   '72000',
            country:      'MX',
        );
    }

    private function makeSupplier(?Rfc $rfc = null): Supplier
    {
        return Supplier::create(
            id:        SupplierId::generate(),
            type:      SupplierType::MORAL,
            rfc:       $rfc ?? new Rfc('ABC123456XYZ', SupplierType::MORAL),
            legalName: new LegalName('Distribuidora Farmacéutica S.A. de C.V.'),
            tradeName: 'DFC',
            address:   $this->makeAddress(),
            phone:     new Phone('2221234567'),
            email:     new Email('contacto@dfc.mx'),
            createdBy: UserId::generate(),
        );
    }

    public function test_create_sets_is_active_true_and_emits_supplier_created(): void
    {
        $supplier = $this->makeSupplier();

        self::assertTrue($supplier->isActive());

        $events = $supplier->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SupplierCreated::class, $events[0]);
    }

    public function test_create_without_rfc_emits_supplier_created(): void
    {
        $supplier = Supplier::create(
            id:        SupplierId::generate(),
            type:      SupplierType::FISICA,
            rfc:       null,
            legalName: new LegalName('Proveedor Extranjero'),
            tradeName: null,
            address:   $this->makeAddress(),
            phone:     null,
            email:     null,
            createdBy: null,
        );

        self::assertNull($supplier->getRfc());
        $events = $supplier->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SupplierCreated::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $supplier = Supplier::reconstitute(
            id:        SupplierId::generate(),
            type:      SupplierType::MORAL,
            rfc:       new Rfc('ABC123456XYZ', SupplierType::MORAL),
            legalName: new LegalName('Distribuidora S.A.'),
            tradeName: null,
            address:   $this->makeAddress(),
            phone:     null,
            email:     null,
            isActive:  true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertEmpty($supplier->releaseEvents());
    }

    public function test_type_is_immutable_after_create(): void
    {
        $supplier = $this->makeSupplier();
        self::assertSame(SupplierType::MORAL, $supplier->getType());
    }

    public function test_update_changes_legal_name_and_emits_supplier_updated(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->releaseEvents();

        $supplier->update(
            legalName: new LegalName('Nueva Distribuidora S.A.'),
            tradeName: 'ND',
            address:   $this->makeAddress(),
            phone:     null,
            email:     null,
        );

        self::assertSame('Nueva Distribuidora S.A.', $supplier->getLegalName()->value);
        self::assertSame('ND', $supplier->getTradeName());

        $events = $supplier->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SupplierUpdated::class, $events[0]);
    }

    public function test_update_with_no_changes_still_emits_supplier_updated(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->releaseEvents();

        $supplier->update(
            legalName: new LegalName('Distribuidora Farmacéutica S.A. de C.V.'),
            tradeName: 'DFC',
            address:   $this->makeAddress(),
            phone:     new Phone('2221234567'),
            email:     new Email('contacto@dfc.mx'),
        );

        $events = $supplier->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SupplierUpdated::class, $events[0]);
    }

    public function test_update_changes_array_contains_changed_fields(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->releaseEvents();

        $supplier->update(
            legalName: new LegalName('Nueva Razón Social S.A.'),
            tradeName: 'DFC',
            address:   $this->makeAddress(),
            phone:     new Phone('2221234567'),
            email:     new Email('contacto@dfc.mx'),
        );

        /** @var SupplierUpdated $event */
        $event = $supplier->releaseEvents()[0];
        self::assertArrayHasKey('legal_name', $event->changes);
        self::assertSame('Distribuidora Farmacéutica S.A. de C.V.', $event->changes['legal_name']['old']);
        self::assertSame('Nueva Razón Social S.A.', $event->changes['legal_name']['new']);
    }

    public function test_deactivate_sets_is_active_false_and_emits_supplier_deactivated(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->releaseEvents();

        $supplier->deactivate();

        self::assertFalse($supplier->isActive());

        $events = $supplier->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SupplierDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->deactivate();
        $supplier->releaseEvents();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('El proveedor ya está inactivo.');

        $supplier->deactivate();
    }

    public function test_release_events_clears_internal_events(): void
    {
        $supplier = $this->makeSupplier();

        $first  = $supplier->releaseEvents();
        $second = $supplier->releaseEvents();

        self::assertCount(1, $first);
        self::assertEmpty($second);
    }

    public function test_activate_sets_is_active_true(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->deactivate();
        $supplier->releaseEvents();

        $supplier->activate();

        self::assertTrue($supplier->isActive());
    }
}
