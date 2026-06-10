<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Classifications/Domain/Model/MedicationClassificationTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Classifications\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationCreated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationDeactivated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationUpdated;
use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;
use PHPUnit\Framework\TestCase;

final class MedicationClassificationTest extends TestCase
{
    private function make(
        LgsGroup $group = LgsGroup::I,
        PrescriptionType $type = PrescriptionType::CON_CODIGO_BARRAS,
    ): MedicationClassification {
        return MedicationClassification::create(
            id: ClassificationId::generate(),
            lgsGroup: $group,
            name: new ClassificationName('Estupefacientes'),
            prescriptionType: $type,
            validityDays: 30,
            validityNote: 'Receta con código de barras.',
            createdBy: UserId::generate(),
        );
    }

    public function test_create_emits_classification_created_event(): void
    {
        $c = $this->make();

        $events = $c->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(ClassificationCreated::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $c = MedicationClassification::reconstitute(
            id: ClassificationId::generate(),
            lgsGroup: LgsGroup::II,
            name: new ClassificationName('Psicotrópicos II'),
            prescriptionType: PrescriptionType::NORMAL,
            validityDays: 30,
            validityNote: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertEmpty($c->releaseEvents());
    }

    public function test_is_controlled_returns_true_for_groups_i_to_i_v_b(): void
    {
        foreach ([LgsGroup::I, LgsGroup::II, LgsGroup::III, LgsGroup::IV_A, LgsGroup::IV_B] as $group) {
            $c = $this->make($group, PrescriptionType::NORMAL);
            self::assertTrue($c->isControlled(), "Expected controlled for group {$group->value}");
        }
    }

    public function test_is_controlled_returns_false_for_groups_v_and_vi(): void
    {
        foreach ([LgsGroup::V, LgsGroup::VI] as $group) {
            $c = $this->make($group, PrescriptionType::SIN_RECETA);
            self::assertFalse($c->isControlled(), "Expected not controlled for group {$group->value}");
        }
    }

    public function test_update_changes_name_and_prescription_type_and_emits_classification_updated(): void
    {
        $c = $this->make();
        $c->releaseEvents();

        $c->update(
            new ClassificationName('Estupefacientes Actualizado'),
            PrescriptionType::NORMAL,
            15,
            'Nueva nota',
        );

        self::assertSame('Estupefacientes Actualizado', $c->getName()->value);
        self::assertSame(PrescriptionType::NORMAL, $c->getPrescriptionType());

        $events = $c->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ClassificationUpdated::class, $events[0]);
    }

    public function test_update_sets_updated_at_to_current_time(): void
    {
        $c = $this->make();
        $before = $c->getUpdatedAt();

        $c->update(
            new ClassificationName('Nuevo nombre'),
            PrescriptionType::NORMAL,
            null,
            null,
        );

        self::assertGreaterThanOrEqual($before, $c->getUpdatedAt());
    }

    public function test_deactivate_sets_is_active_false_and_emits_classification_deactivated(): void
    {
        $c = $this->make();
        $c->releaseEvents();

        $c->deactivate();

        self::assertFalse($c->isActive());

        $events = $c->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ClassificationDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $c = $this->make();
        $c->deactivate();
        $c->releaseEvents();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('La clasificación ya está inactiva.');

        $c->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $c = $this->make();

        $first = $c->releaseEvents();
        $second = $c->releaseEvents();

        self::assertCount(1, $first);
        self::assertEmpty($second);
    }
}
