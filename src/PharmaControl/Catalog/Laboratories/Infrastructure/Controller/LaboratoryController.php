<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Infrastructure/Controller/LaboratoryController.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Infrastructure\Controller;

use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Application\UseCase\CreateLaboratory\CreateLaboratoryCommand;
use PharmaControl\Catalog\Laboratories\Application\UseCase\CreateLaboratory\CreateLaboratoryUseCase;
use PharmaControl\Catalog\Laboratories\Application\UseCase\DeactivateLaboratory\DeactivateLaboratoryCommand;
use PharmaControl\Catalog\Laboratories\Application\UseCase\DeactivateLaboratory\DeactivateLaboratoryUseCase;
use PharmaControl\Catalog\Laboratories\Application\UseCase\GetLaboratories\GetLaboratoriesQuery;
use PharmaControl\Catalog\Laboratories\Application\UseCase\GetLaboratories\GetLaboratoriesUseCase;
use PharmaControl\Catalog\Laboratories\Application\UseCase\UpdateLaboratory\UpdateLaboratoryCommand;
use PharmaControl\Catalog\Laboratories\Application\UseCase\UpdateLaboratory\UpdateLaboratoryUseCase;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Exception\LaboratoryNotFoundException;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;

final class LaboratoryController
{
    public function __construct(
        private readonly CreateLaboratoryUseCase $create,
        private readonly UpdateLaboratoryUseCase $update,
        private readonly DeactivateLaboratoryUseCase $deactivate,
        private readonly GetLaboratoriesUseCase $get,
        private readonly LaboratoryRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetLaboratoriesQuery(
            search: $data['search'] ?? null,
            countryCode: $data['country_code'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage: (int) ($data['per_page'] ?? 20),
            page: (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): LaboratoryDTO
    {
        return ($this->create)(new CreateLaboratoryCommand(
            name: $data['name'],
            countryCode: $data['country_code'],
            website: $data['website'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): LaboratoryDTO
    {
        $laboratory = $this->repository->findById(new LaboratoryId($id));
        if ($laboratory === null) {
            throw new LaboratoryNotFoundException($id);
        }

        return LaboratoryDTO::fromDomain($laboratory);
    }

    public function update(string $id, array $data): LaboratoryDTO
    {
        return ($this->update)(new UpdateLaboratoryCommand(
            id: $id,
            name: $data['name'],
            countryCode: $data['country_code'],
            website: $data['website'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateLaboratoryCommand($id, $actorUserId));
    }
}
