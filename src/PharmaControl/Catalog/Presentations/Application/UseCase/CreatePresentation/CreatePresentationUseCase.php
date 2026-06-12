<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\CreatePresentation;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicateAbbreviationException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicatePresentationNameException;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;

final class CreatePresentationUseCase
{
    public function __construct(
        private readonly PresentationRepositoryContract $repository,
        private readonly EventPublisherContract         $events,
    ) {}

    public function __invoke(CreatePresentationCommand $command): PresentationDTO
    {
        $name         = new PresentationName($command->name);
        $abbreviation = new Abbreviation($command->abbreviation);

        if ($this->repository->findByName($name) !== null) {
            throw new DuplicatePresentationNameException($command->name);
        }

        if ($this->repository->findByAbbreviation($abbreviation) !== null) {
            throw new DuplicateAbbreviationException($command->abbreviation);
        }

        if ($command->description !== null && mb_strlen($command->description) > 500) {
            throw new \InvalidArgumentException('La descripción no puede exceder 500 caracteres.');
        }

        $presentation = Presentation::create(
            PresentationId::generate(),
            $name,
            $abbreviation,
            $command->description,
            new UserId($command->actorUserId),
        );

        $this->repository->save($presentation);

        foreach ($presentation->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return PresentationDTO::fromDomain($presentation);
    }
}
