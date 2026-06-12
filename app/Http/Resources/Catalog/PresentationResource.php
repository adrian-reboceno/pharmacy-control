<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;

class PresentationResource extends JsonResource
{
    public function __construct(PresentationDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var PresentationDTO $dto */
        $dto = $this->resource;

        return [
            'id'           => $dto->id,
            'name'         => $dto->name,
            'abbreviation' => $dto->abbreviation,
            'description'  => $dto->description,
            'is_active'    => $dto->isActive,
            'created_by'   => $dto->createdBy,
            'created_at'   => $dto->createdAt,
            'updated_at'   => $dto->updatedAt,
        ];
    }
}
