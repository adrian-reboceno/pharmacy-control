<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncIngredientToMongoJob;
use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientCreated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientDeactivated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientUpdated;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;

final class SyncIngredientListener
{
    public function __construct(
        private readonly IngredientRepositoryContract $repository,
    ) {}

    public function handleCreated(IngredientCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(IngredientUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(IngredientDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    /**
     * Relee el AR completo desde Postgres en lugar de confiar en datos
     * parciales del evento — garantiza que el documento Mongo siempre
     * refleja el estado actual completo.
     */
    private function dispatchSync(IngredientId $id): void
    {
        $ingredient = $this->repository->findById($id);

        if ($ingredient === null) {
            return;
        }

        $dto = IngredientDTO::fromDomain($ingredient);

        SyncIngredientToMongoJob::dispatch([
            'id' => $dto->id,
            'name' => $dto->name,
            'dci_code' => $dto->dciCode,
            'cas_number' => $dto->casNumber,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ]);
    }
}
