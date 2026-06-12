<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;

class RouteResource extends JsonResource
{
    public function __construct(RouteDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var RouteDTO $dto */
        $dto = $this->resource;

        return [
            'id'          => $dto->id,
            'name'        => $dto->name,
            'code'        => $dto->code,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
            'created_by'  => $dto->createdBy,
            'created_at'  => $dto->createdAt,
            'updated_at'  => $dto->updatedAt,
        ];
    }
}
