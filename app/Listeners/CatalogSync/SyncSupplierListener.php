<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncSupplierToMongoJob;
use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Event\SupplierCreated;
use PharmaControl\Suppliers\Domain\Event\SupplierDeactivated;
use PharmaControl\Suppliers\Domain\Event\SupplierUpdated;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;

final class SyncSupplierListener
{
    public function __construct(
        private readonly SupplierRepositoryContract $repository,
    ) {}

    public function handleCreated(SupplierCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(SupplierUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(SupplierDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    /**
     * Relee el AR completo desde Postgres en lugar de confiar en datos
     * parciales del evento — garantiza que el documento Mongo siempre
     * refleja el estado actual completo, incluyendo el VO Address embebido.
     */
    private function dispatchSync(SupplierId $id): void
    {
        $supplier = $this->repository->findById($id);

        if ($supplier === null) {
            return;
        }

        $dto = SupplierDTO::fromDomain($supplier);

        SyncSupplierToMongoJob::dispatch([
            'id' => $dto->id,
            'type' => $dto->type,
            'rfc' => $dto->rfc,
            'legal_name' => $dto->legalName,
            'trade_name' => $dto->tradeName,
            'address' => [
                'street' => $dto->address->street,
                'ext_number' => $dto->address->extNumber,
                'int_number' => $dto->address->intNumber,
                'neighborhood' => $dto->address->neighborhood,
                'municipality' => $dto->address->municipality,
                'state' => $dto->address->state,
                'postal_code' => $dto->address->postalCode,
                'country' => $dto->address->country,
            ],
            'phone' => $dto->phone,
            'email' => $dto->email,
            'is_active' => $dto->isActive,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ]);
    }
}
