<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Application/UseCase/DeactivateClassification/DeactivateClassificationUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Application\UseCase\DeactivateClassification;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotFoundException;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;

final class DeactivateClassificationUseCase
{
    public function __construct(
        private readonly ClassificationRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateClassificationCommand $command): void
    {
        $classification = $this->repository->findById(new ClassificationId($command->id));
        if ($classification === null) {
            throw new ClassificationNotFoundException($command->id);
        }

        $classification->deactivate();
        $this->repository->save($classification);

        foreach ($classification->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
