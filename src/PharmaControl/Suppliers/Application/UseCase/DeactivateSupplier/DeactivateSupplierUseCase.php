<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\DeactivateSupplier;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Exception\SupplierNotFoundException;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;

final class DeactivateSupplierUseCase
{
    public function __construct(
        private readonly SupplierRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateSupplierCommand $command): void
    {
        $supplier = $this->repository->findById(new SupplierId($command->id));
        if ($supplier === null) {
            throw new SupplierNotFoundException($command->id);
        }

        $supplier->deactivate();

        $this->repository->save($supplier);

        foreach ($supplier->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
