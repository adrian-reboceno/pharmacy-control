<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Infrastructure/Persistence/Mapper/LaboratoryMapper.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\WebsiteUrl;
use PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Eloquent\Model\EloquentLaboratory;

final class LaboratoryMapper
{
    public function toDomain(EloquentLaboratory $model): Laboratory
    {
        return Laboratory::reconstitute(
            id: new LaboratoryId($model->id),
            name: new LaboratoryName($model->name),
            countryCode: new CountryCode($model->country_code),
            website: $model->website !== null ? new WebsiteUrl($model->website) : null,
            isActive: $model->is_active,
            createdBy: $model->created_by !== null ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toPersistence(Laboratory $lab): array
    {
        return [
            'id' => $lab->getId()->value,
            'name' => $lab->getName()->value,
            'country_code' => $lab->getCountryCode()->value,
            'website' => $lab->getWebsite()?->value,
            'is_active' => $lab->isActive(),
            'created_by' => $lab->getCreatedBy()?->value,
        ];
    }
}
