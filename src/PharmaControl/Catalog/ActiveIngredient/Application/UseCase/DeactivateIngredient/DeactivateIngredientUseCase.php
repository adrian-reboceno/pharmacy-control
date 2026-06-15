<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\DeactivateIngredient;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\IngredientNotFoundException;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;

final class DeactivateIngredientUseCase
{
    public function __construct(
        private readonly IngredientRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateIngredientCommand $command): void
    {
        $ingredient = $this->repository->findById(new IngredientId($command->id));
        if ($ingredient === null) {
            throw new IngredientNotFoundException($command->id);
        }

        $ingredient->deactivate();
        $this->repository->save($ingredient);

        foreach ($ingredient->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
