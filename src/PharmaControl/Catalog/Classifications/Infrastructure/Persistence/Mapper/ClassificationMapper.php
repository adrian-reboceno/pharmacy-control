<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Infrastructure/Persistence/Mapper/ClassificationMapper.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;
use PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Eloquent\Model\EloquentClassification;

final class ClassificationMapper
{
    public function toDomain(EloquentClassification $model): MedicationClassification
    {
        return MedicationClassification::reconstitute(
            id: new ClassificationId($model->id),
            lgsGroup: LgsGroup::from($model->lgs_group),
            name: new ClassificationName($model->name),
            prescriptionType: PrescriptionType::from($model->prescription_type),
            validityDays: $model->validity_days,
            validityNote: $model->validity_note,
            isActive: $model->is_active,
            createdBy: $model->created_by !== null ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toPersistence(MedicationClassification $c): array
    {
        return [
            'id' => $c->getId()->value,
            'lgs_group' => $c->getLgsGroup()->value,
            'name' => $c->getName()->value,
            'prescription_type' => $c->getPrescriptionType()->value,
            'validity_days' => $c->getValidityDays(),
            'validity_note' => $c->getValidityNote(),
            'is_active' => $c->isActive(),
            'created_by' => $c->getCreatedBy()?->value,
        ];
    }
}
