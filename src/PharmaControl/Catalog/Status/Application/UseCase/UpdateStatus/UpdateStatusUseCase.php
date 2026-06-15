<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Application\UseCase\UpdateStatus;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusCodeException;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusNameException;
use PharmaControl\Catalog\Status\Domain\Exception\StatusNotFoundException;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;

final class UpdateStatusUseCase
{
    public function __construct(
        private readonly StatusRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(UpdateStatusCommand $command): StatusDTO
    {
        $status = $this->repository->findById(new StatusId($command->id));
        if ($status === null) {
            throw new StatusNotFoundException($command->id);
        }

        $name = new StatusName($command->name);
        $code = new StatusCode($command->code);

        $existingByName = $this->repository->findByName($name);
        if ($existingByName !== null && !$existingByName->getId()->equals($status->getId())) {
            throw new DuplicateStatusNameException($command->name);
        }

        $existingByCode = $this->repository->findByCode($code);
        if ($existingByCode !== null && !$existingByCode->getId()->equals($status->getId())) {
            throw new DuplicateStatusCodeException($command->code);
        }

        $status->update($name, $code, $command->description);
        $this->repository->save($status);

        foreach ($status->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return StatusDTO::fromDomain($status);
    }
}
