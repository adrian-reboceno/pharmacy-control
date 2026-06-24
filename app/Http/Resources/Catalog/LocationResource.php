<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;

class LocationResource extends JsonResource
{
    public function __construct(LocationDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var LocationDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'level' => $dto->level,
            'level_label' => $dto->levelLabel,
            'parent_id' => $dto->parentId,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
            'is_leaf' => $dto->isLeaf,
            'full_path' => $dto->fullPath,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
