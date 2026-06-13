<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\CreateSupplier;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Exception\DuplicateRfcException;
use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\Email;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Phone;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;

final class CreateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryContract $repository,
        private readonly EventPublisherContract      $events,
    ) {}

    public function __invoke(CreateSupplierCommand $command): SupplierDTO
    {
        $type      = SupplierType::from($command->type);
        $legalName = new LegalName($command->legalName);
        $address   = new Address(
            street:       $command->address['street'],
            extNumber:    $command->address['ext_number'],
            intNumber:    $command->address['int_number'] ?? null,
            neighborhood: $command->address['neighborhood'],
            municipality: $command->address['municipality'],
            state:        $command->address['state'],
            postalCode:   $command->address['postal_code'],
            country:      $command->address['country'] ?? 'MX',
        );
        $phone = $command->phone !== null ? new Phone($command->phone) : null;
        $email = $command->email !== null ? new Email($command->email) : null;

        $rfc = null;
        if ($command->rfc !== null) {
            $rfc = new Rfc($command->rfc, $type);
            if ($this->repository->findByRfc($rfc) !== null) {
                throw new DuplicateRfcException($rfc->value);
            }
        }

        $supplier = Supplier::create(
            id:        SupplierId::generate(),
            type:      $type,
            rfc:       $rfc,
            legalName: $legalName,
            tradeName: $command->tradeName,
            address:   $address,
            phone:     $phone,
            email:     $email,
            createdBy: new UserId($command->actorUserId),
        );

        $this->repository->save($supplier);

        foreach ($supplier->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return SupplierDTO::fromDomain($supplier);
    }
}
