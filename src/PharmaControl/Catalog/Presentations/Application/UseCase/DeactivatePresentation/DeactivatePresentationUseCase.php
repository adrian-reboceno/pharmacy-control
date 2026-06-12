<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\DeactivatePresentation;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Exception\PresentationNotFoundException;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;

final class DeactivatePresentationUseCase
{
    public function __construct(
        private readonly PresentationRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivatePresentationCommand $command): void
    {
        $presentation = $this->repository->findById(new PresentationId($command->id));
        if ($presentation === null) {
            throw new PresentationNotFoundException($command->id);
        }

        $presentation->deactivate();
        $this->repository->save($presentation);

        foreach ($presentation->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
