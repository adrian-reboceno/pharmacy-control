<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\UpdateIngredient;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateCasNumberException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateDciCodeException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateIngredientNameException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\IngredientNotFoundException;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;

final class UpdateIngredientUseCase
{
    public function __construct(
        private readonly IngredientRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(UpdateIngredientCommand $command): IngredientDTO
    {
        $ingredient = $this->repository->findById(new IngredientId($command->id));
        if ($ingredient === null) {
            throw new IngredientNotFoundException($command->id);
        }

        $name = new IngredientName($command->name);
        $dciCode = new DciCode($command->dciCode);

        $existingByName = $this->repository->findByName($name);
        if ($existingByName !== null && ! $existingByName->getId()->equals($ingredient->getId())) {
            throw new DuplicateIngredientNameException($command->name);
        }

        $existingByDci = $this->repository->findByDciCode($dciCode);
        if ($existingByDci !== null && ! $existingByDci->getId()->equals($ingredient->getId())) {
            throw new DuplicateDciCodeException($command->dciCode);
        }

        $casNumber = null;
        if ($command->casNumber !== null) {
            $casNumber = new CasNumber($command->casNumber);
            $existingByCas = $this->repository->findByCasNumber($casNumber);
            if ($existingByCas !== null && ! $existingByCas->getId()->equals($ingredient->getId())) {
                throw new DuplicateCasNumberException($command->casNumber);
            }
        }

        $ingredient->update($name, $dciCode, $casNumber, $command->description);
        $this->repository->save($ingredient);

        foreach ($ingredient->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return IngredientDTO::fromDomain($ingredient);
    }
}
