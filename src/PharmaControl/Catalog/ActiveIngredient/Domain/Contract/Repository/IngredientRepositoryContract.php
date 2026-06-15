<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository;

use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;

interface IngredientRepositoryContract
{
    public function save(ActiveIngredient $ingredient): void;

    public function findById(IngredientId $id): ?ActiveIngredient;

    public function findByName(IngredientName $name): ?ActiveIngredient;

    public function findByDciCode(DciCode $code): ?ActiveIngredient;

    public function findByCasNumber(CasNumber $cas): ?ActiveIngredient;

    /**
     * @param array{
     *   search?:    string,
     *   is_active?: bool,
     *   per_page?:  int,
     *   page?:      int,
     * } $filters
     * @return array{
     *   data:         ActiveIngredient[],
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     * }
     */
    public function findAll(array $filters = []): array;
}
