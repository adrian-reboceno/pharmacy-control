<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/UseCase/DeactivateLaboratory/DeactivateLaboratoryUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\UseCase\DeactivateLaboratory;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Exception\LaboratoryNotFoundException;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;

final class DeactivateLaboratoryUseCase
{
    public function __construct(
        private readonly LaboratoryRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateLaboratoryCommand $command): void
    {
        $laboratory = $this->repository->findById(new LaboratoryId($command->id));
        if ($laboratory === null) {
            throw new LaboratoryNotFoundException($command->id);
        }

        $laboratory->deactivate();
        $this->repository->save($laboratory);

        foreach ($laboratory->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
