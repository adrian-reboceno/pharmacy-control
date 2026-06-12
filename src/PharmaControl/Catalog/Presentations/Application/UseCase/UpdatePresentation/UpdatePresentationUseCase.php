<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\UpdatePresentation;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicateAbbreviationException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicatePresentationNameException;
use PharmaControl\Catalog\Presentations\Domain\Exception\PresentationNotFoundException;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;

final class UpdatePresentationUseCase
{
    public function __construct(
        private readonly PresentationRepositoryContract $repository,
        private readonly EventPublisherContract         $events,
    ) {}

    public function __invoke(UpdatePresentationCommand $command): PresentationDTO
    {
        $presentation = $this->repository->findById(new PresentationId($command->id));
        if ($presentation === null) {
            throw new PresentationNotFoundException($command->id);
        }

        $name         = new PresentationName($command->name);
        $abbreviation = new Abbreviation($command->abbreviation);

        $existingByName = $this->repository->findByName($name);
        if ($existingByName !== null && !$existingByName->getId()->equals($presentation->getId())) {
            throw new DuplicatePresentationNameException($command->name);
        }

        $existingByAbbreviation = $this->repository->findByAbbreviation($abbreviation);
        if ($existingByAbbreviation !== null && !$existingByAbbreviation->getId()->equals($presentation->getId())) {
            throw new DuplicateAbbreviationException($command->abbreviation);
        }

        $presentation->update($name, $abbreviation, $command->description);
        $this->repository->save($presentation);

        foreach ($presentation->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return PresentationDTO::fromDomain($presentation);
    }
}
