<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Infrastructure\Controller;

use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\CreateIngredient\CreateIngredientCommand;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\CreateIngredient\CreateIngredientUseCase;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\DeactivateIngredient\DeactivateIngredientCommand;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\DeactivateIngredient\DeactivateIngredientUseCase;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\GetIngredients\GetIngredientsQuery;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\GetIngredients\GetIngredientsUseCase;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\UpdateIngredient\UpdateIngredientCommand;
use PharmaControl\Catalog\ActiveIngredient\Application\UseCase\UpdateIngredient\UpdateIngredientUseCase;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\IngredientNotFoundException;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;

final class IngredientController
{
    public function __construct(
        private readonly CreateIngredientUseCase $create,
        private readonly UpdateIngredientUseCase $update,
        private readonly DeactivateIngredientUseCase $deactivate,
        private readonly GetIngredientsUseCase $get,
        private readonly IngredientRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetIngredientsQuery(
            search: $data['search'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage: (int) ($data['per_page'] ?? 20),
            page: (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): IngredientDTO
    {
        return ($this->create)(new CreateIngredientCommand(
            name: $data['name'],
            dciCode: $data['dci_code'],
            casNumber: $data['cas_number'] ?? null,
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): IngredientDTO
    {
        $ingredient = $this->repository->findById(new IngredientId($id));
        if ($ingredient === null) {
            throw new IngredientNotFoundException($id);
        }

        return IngredientDTO::fromDomain($ingredient);
    }

    public function update(string $id, array $data): IngredientDTO
    {
        return ($this->update)(new UpdateIngredientCommand(
            id: $id,
            name: $data['name'],
            dciCode: $data['dci_code'],
            casNumber: $data['cas_number'] ?? null,
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateIngredientCommand($id, $actorUserId));
    }
}
