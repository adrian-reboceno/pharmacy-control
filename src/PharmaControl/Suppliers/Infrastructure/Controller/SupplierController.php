<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Infrastructure\Controller;

use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Application\UseCase\CreateSupplier\CreateSupplierCommand;
use PharmaControl\Suppliers\Application\UseCase\CreateSupplier\CreateSupplierUseCase;
use PharmaControl\Suppliers\Application\UseCase\DeactivateSupplier\DeactivateSupplierCommand;
use PharmaControl\Suppliers\Application\UseCase\DeactivateSupplier\DeactivateSupplierUseCase;
use PharmaControl\Suppliers\Application\UseCase\GetSuppliers\GetSuppliersQuery;
use PharmaControl\Suppliers\Application\UseCase\GetSuppliers\GetSuppliersUseCase;
use PharmaControl\Suppliers\Application\UseCase\UpdateSupplier\UpdateSupplierCommand;
use PharmaControl\Suppliers\Application\UseCase\UpdateSupplier\UpdateSupplierUseCase;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Domain\Exception\SupplierNotFoundException;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;

final class SupplierController
{
    public function __construct(
        private readonly CreateSupplierUseCase     $create,
        private readonly UpdateSupplierUseCase     $update,
        private readonly DeactivateSupplierUseCase $deactivate,
        private readonly GetSuppliersUseCase       $get,
        private readonly SupplierRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetSuppliersQuery(
            search:   $data['search'] ?? null,
            type:     $data['type'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage:  (int) ($data['per_page'] ?? 20),
            page:     (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): SupplierDTO
    {
        return ($this->create)(new CreateSupplierCommand(
            type:        $data['type'],
            rfc:         $data['rfc'] ?? null,
            legalName:   $data['legal_name'],
            tradeName:   $data['trade_name'] ?? null,
            address:     $data['address'],
            phone:       $data['phone'] ?? null,
            email:       $data['email'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): SupplierDTO
    {
        $supplier = $this->repository->findById(new SupplierId($id));
        if ($supplier === null) {
            throw new SupplierNotFoundException($id);
        }

        return SupplierDTO::fromDomain($supplier);
    }

    public function update(string $id, array $data): SupplierDTO
    {
        return ($this->update)(new UpdateSupplierCommand(
            id:          $id,
            legalName:   $data['legal_name'],
            tradeName:   $data['trade_name'] ?? null,
            address:     $data['address'],
            phone:       $data['phone'] ?? null,
            email:       $data['email'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateSupplierCommand($id, $actorUserId));
    }
}
