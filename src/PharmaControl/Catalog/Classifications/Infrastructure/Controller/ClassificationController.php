<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Infrastructure/Controller/ClassificationController.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Infrastructure\Controller;

use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;
use PharmaControl\Catalog\Classifications\Application\UseCase\DeactivateClassification\DeactivateClassificationCommand;
use PharmaControl\Catalog\Classifications\Application\UseCase\DeactivateClassification\DeactivateClassificationUseCase;
use PharmaControl\Catalog\Classifications\Application\UseCase\GetClassifications\GetClassificationsQuery;
use PharmaControl\Catalog\Classifications\Application\UseCase\GetClassifications\GetClassificationsUseCase;
use PharmaControl\Catalog\Classifications\Application\UseCase\UpdateClassification\UpdateClassificationCommand;
use PharmaControl\Catalog\Classifications\Application\UseCase\UpdateClassification\UpdateClassificationUseCase;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotFoundException;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;

final class ClassificationController
{
    public function __construct(
        private readonly UpdateClassificationUseCase $update,
        private readonly DeactivateClassificationUseCase $deactivate,
        private readonly GetClassificationsUseCase $get,
        private readonly ClassificationRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetClassificationsQuery(
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            isControlled: isset($data['is_controlled']) ? (bool) $data['is_controlled'] : null,
            perPage: (int) ($data['per_page'] ?? 20),
            page: (int) ($data['page'] ?? 1),
        ));
    }

    public function show(string $id): ClassificationDTO
    {
        $classification = $this->repository->findById(new ClassificationId($id));
        if ($classification === null) {
            throw new ClassificationNotFoundException($id);
        }

        return ClassificationDTO::fromDomain($classification);
    }

    public function update(string $id, array $data): ClassificationDTO
    {
        return ($this->update)(new UpdateClassificationCommand(
            id: $id,
            name: $data['name'],
            prescriptionType: $data['prescription_type'],
            validityDays: $data['validity_days'] ?? null,
            validityNote: $data['validity_note'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateClassificationCommand($id, $actorUserId));
    }
}
