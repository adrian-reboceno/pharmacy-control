<?php

// ── ARCHIVO: app/Http/Resources/Catalog/LaboratoryResource.php ──
declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;

class LaboratoryResource extends JsonResource
{
    public function __construct(LaboratoryDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var LaboratoryDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'country_code' => $dto->countryCode,
            'website' => $dto->website,
            'is_active' => $dto->isActive,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
