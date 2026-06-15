<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Infrastructure\Controller;

use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Application\UseCase\CreateStatus\CreateStatusCommand;
use PharmaControl\Catalog\Status\Application\UseCase\CreateStatus\CreateStatusUseCase;
use PharmaControl\Catalog\Status\Application\UseCase\DeactivateStatus\DeactivateStatusCommand;
use PharmaControl\Catalog\Status\Application\UseCase\DeactivateStatus\DeactivateStatusUseCase;
use PharmaControl\Catalog\Status\Application\UseCase\GetStatuses\GetStatusesQuery;
use PharmaControl\Catalog\Status\Application\UseCase\GetStatuses\GetStatusesUseCase;
use PharmaControl\Catalog\Status\Application\UseCase\UpdateStatus\UpdateStatusCommand;
use PharmaControl\Catalog\Status\Application\UseCase\UpdateStatus\UpdateStatusUseCase;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Exception\StatusNotFoundException;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;

final class StatusController
{
    public function __construct(
        private readonly CreateStatusUseCase $create,
        private readonly UpdateStatusUseCase $update,
        private readonly DeactivateStatusUseCase $deactivate,
        private readonly GetStatusesUseCase $get,
        private readonly StatusRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetStatusesQuery(
            search: $data['search'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage: (int) ($data['per_page'] ?? 20),
            page: (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): StatusDTO
    {
        return ($this->create)(new CreateStatusCommand(
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): StatusDTO
    {
        $status = $this->repository->findById(new StatusId($id));
        if ($status === null) {
            throw new StatusNotFoundException($id);
        }

        return StatusDTO::fromDomain($status);
    }

    public function update(string $id, array $data): StatusDTO
    {
        return ($this->update)(new UpdateStatusCommand(
            id: $id,
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateStatusCommand($id, $actorUserId));
    }
}
