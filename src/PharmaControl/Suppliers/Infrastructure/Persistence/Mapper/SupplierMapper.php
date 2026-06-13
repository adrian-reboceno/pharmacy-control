<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\Email;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Phone;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;
use PharmaControl\Suppliers\Infrastructure\Persistence\Eloquent\Model\EloquentSupplier;

final class SupplierMapper
{
    public function toDomain(EloquentSupplier $model): Supplier
    {
        $type = SupplierType::from($model->type);

        return Supplier::reconstitute(
            id:           new SupplierId($model->id),
            type:         $type,
            rfc:          $model->rfc ? new Rfc($model->rfc, $type) : null,
            legalName:    new LegalName($model->legal_name),
            tradeName:    $model->trade_name,
            address:      new Address(
                street:       $model->address_street,
                extNumber:    $model->address_ext_number,
                intNumber:    $model->address_int_number,
                neighborhood: $model->address_neighborhood,
                municipality: $model->address_municipality,
                state:        $model->address_state,
                postalCode:   $model->address_postal_code,
                country:      $model->address_country,
            ),
            phone:        $model->phone ? new Phone($model->phone) : null,
            email:        $model->email ? new Email($model->email) : null,
            isActive:     $model->is_active,
            createdBy:    $model->created_by ? new UserId($model->created_by) : null,
            createdAt:    $model->created_at,
            updatedAt:    $model->updated_at,
        );
    }

    public function toPersistence(Supplier $s): array
    {
        $address = $s->getAddress();

        return [
            'id'                   => $s->getId()->value,
            'type'                 => $s->getType()->value,
            'rfc'                  => $s->getRfc()?->value,
            'legal_name'           => $s->getLegalName()->value,
            'trade_name'           => $s->getTradeName(),
            'address_street'       => $address->street,
            'address_ext_number'   => $address->extNumber,
            'address_int_number'   => $address->intNumber,
            'address_neighborhood' => $address->neighborhood,
            'address_municipality' => $address->municipality,
            'address_state'        => $address->state,
            'address_postal_code'  => $address->postalCode,
            'address_country'      => $address->country,
            'phone'                => $s->getPhone()?->value,
            'email'                => $s->getEmail()?->value,
            'is_active'            => $s->isActive(),
            'created_by'           => $s->getCreatedBy()?->value,
        ];
    }
}
