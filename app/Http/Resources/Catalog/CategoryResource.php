<?php

// ── ARCHIVO: app/Http/Resources/Catalog/CategoryResource.php ──
declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;

class CategoryResource extends JsonResource
{
    public function __construct(CategoryDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var CategoryDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'parent_id' => $dto->parentId,
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
            'is_root' => $dto->isRoot,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
