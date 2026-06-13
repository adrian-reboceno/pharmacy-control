<?php

declare(strict_types=1);

namespace App\Http\Resources\Suppliers;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->typeLabel,
            'rfc' => $this->rfc,
            'legal_name' => $this->legalName,
            'trade_name' => $this->tradeName,
            'address' => [
                'street' => $this->address->street,
                'ext_number' => $this->address->extNumber,
                'int_number' => $this->address->intNumber,
                'neighborhood' => $this->address->neighborhood,
                'municipality' => $this->address->municipality,
                'state' => $this->address->state,
                'postal_code' => $this->address->postalCode,
                'country' => $this->address->country,
                'full_address' => $this->address->fullAddress,
            ],
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
