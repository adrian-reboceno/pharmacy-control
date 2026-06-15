<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\CreateIngredient;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateCasNumberException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateDciCodeException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateIngredientNameException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;

final class CreateIngredientUseCase
{
    public function __construct(
        private readonly IngredientRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(CreateIngredientCommand $command): IngredientDTO
    {
        $name = new IngredientName($command->name);
        $dciCode = new DciCode($command->dciCode);

        if ($this->repository->findByName($name) !== null) {
            throw new DuplicateIngredientNameException($command->name);
        }

        if ($this->repository->findByDciCode($dciCode) !== null) {
            throw new DuplicateDciCodeException($command->dciCode);
        }

        $casNumber = null;
        if ($command->casNumber !== null) {
            $casNumber = new CasNumber($command->casNumber);
            if ($this->repository->findByCasNumber($casNumber) !== null) {
                throw new DuplicateCasNumberException($command->casNumber);
            }
        }

        $ingredient = ActiveIngredient::create(
            IngredientId::generate(),
            $name,
            $dciCode,
            $casNumber,
            $command->description,
            new UserId($command->actorUserId),
        );

        $this->repository->save($ingredient);

        foreach ($ingredient->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return IngredientDTO::fromDomain($ingredient);
    }
}
