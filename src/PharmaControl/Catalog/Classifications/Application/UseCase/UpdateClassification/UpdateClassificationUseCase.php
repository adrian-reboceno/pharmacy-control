<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Application/UseCase/UpdateClassification/UpdateClassificationUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Application\UseCase\UpdateClassification;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotFoundException;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;

final class UpdateClassificationUseCase
{
    public function __construct(
        private readonly ClassificationRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(UpdateClassificationCommand $command): ClassificationDTO
    {
        $classification = $this->repository->findById(new ClassificationId($command->id));
        if ($classification === null) {
            throw new ClassificationNotFoundException($command->id);
        }

        $prescriptionType = PrescriptionType::tryFrom($command->prescriptionType);
        if ($prescriptionType === null) {
            throw new \InvalidArgumentException(
                "Tipo de receta inválido: {$command->prescriptionType}"
            );
        }

        $name = new ClassificationName($command->name);

        $classification->update($name, $prescriptionType, $command->validityDays, $command->validityNote);
        $this->repository->save($classification);

        foreach ($classification->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return ClassificationDTO::fromDomain($classification);
    }
}
