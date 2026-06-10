<?php

// ── ARCHIVO: app/Http/Resources/Catalog/ClassificationResource.php ──
declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;

class ClassificationResource extends JsonResource
{
    public function __construct(ClassificationDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var ClassificationDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'lgs_group' => $dto->lgsGroup,
            'lgs_group_label' => $dto->lgsGroupLabel,
            'name' => $dto->name,
            'prescription_type' => $dto->prescriptionType,
            'prescription_type_label' => $dto->prescriptionTypeLabel,
            'validity_days' => $dto->validityDays,
            'validity_note' => $dto->validityNote,
            'is_controlled' => $dto->isControlled,
            'is_active' => $dto->isActive,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
