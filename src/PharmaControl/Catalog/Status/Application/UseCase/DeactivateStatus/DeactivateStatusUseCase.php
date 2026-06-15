<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Application\UseCase\DeactivateStatus;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Exception\StatusNotFoundException;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;

final class DeactivateStatusUseCase
{
    public function __construct(
        private readonly StatusRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateStatusCommand $command): void
    {
        $status = $this->repository->findById(new StatusId($command->id));
        if ($status === null) {
            throw new StatusNotFoundException($command->id);
        }

        $status->deactivate();
        $this->repository->save($status);

        foreach ($status->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
