<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Application\UseCase\CreateStatus;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusCodeException;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusNameException;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;

final class CreateStatusUseCase
{
    public function __construct(
        private readonly StatusRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(CreateStatusCommand $command): StatusDTO
    {
        $name = new StatusName($command->name);
        $code = new StatusCode($command->code);

        if ($this->repository->findByName($name) !== null) {
            throw new DuplicateStatusNameException($command->name);
        }

        if ($this->repository->findByCode($code) !== null) {
            throw new DuplicateStatusCodeException($command->code);
        }

        if ($command->description !== null && mb_strlen($command->description) > 255) {
            throw new \InvalidArgumentException('La descripción no puede exceder 255 caracteres.');
        }

        $status = ProductStatus::create(
            StatusId::generate(),
            $name,
            $code,
            $command->description,
            new UserId($command->actorUserId),
        );

        $this->repository->save($status);

        foreach ($status->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return StatusDTO::fromDomain($status);
    }
}
