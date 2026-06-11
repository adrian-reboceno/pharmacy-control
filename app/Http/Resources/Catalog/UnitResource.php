<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;

class UnitResource extends JsonResource
{
    public function __construct(UnitDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var UnitDTO $dto */
        $dto = $this->resource;

        return [
            'id'         => $dto->id,
            'name'       => $dto->name,
            'symbol'     => $dto->symbol,
            'type'       => $dto->type,
            'type_label' => $dto->typeLabel,
            'is_active'  => $dto->isActive,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
