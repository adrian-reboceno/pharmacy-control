<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\UpdateSupplier;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Exception\SupplierNotFoundException;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\Email;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Phone;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;

final class UpdateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryContract $repository,
        private readonly EventPublisherContract      $events,
    ) {}

    public function __invoke(UpdateSupplierCommand $command): SupplierDTO
    {
        $supplier = $this->repository->findById(new SupplierId($command->id));
        if ($supplier === null) {
            throw new SupplierNotFoundException($command->id);
        }

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

        $supplier->update($legalName, $command->tradeName, $address, $phone, $email);

        $this->repository->save($supplier);

        foreach ($supplier->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return SupplierDTO::fromDomain($supplier);
    }
}
