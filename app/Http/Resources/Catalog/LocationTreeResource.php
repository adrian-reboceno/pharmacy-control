<?php
declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationTreeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'level'       => $this->level,
            'level_label' => $this->levelLabel,
            'parent_id'   => $this->parentId,
            'description' => $this->description,
            'is_active'   => $this->isActive,
            'is_leaf'     => $this->isLeaf,
            'full_path'   => $this->fullPath,
            'children'    => LocationTreeResource::collection($this->children ?? []),
        ];
    }
}
