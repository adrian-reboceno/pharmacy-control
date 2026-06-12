<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository;

use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;

interface UnitRepositoryContract
{
    public function save(UnitOfMeasurement $unit): void;

    public function findById(UnitId $id): ?UnitOfMeasurement;

    public function findByName(UnitName $name): ?UnitOfMeasurement;

    public function findBySymbol(UnitSymbol $symbol): ?UnitOfMeasurement;

    /**
     * @param array{
     *   type?:      string,
     *   search?:    string,
     *   is_active?: bool,
     *   per_page?:  int,
     *   page?:      int,
     * } $filters
     * @return array{
     *   data:         UnitOfMeasurement[],
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     * }
     */
    public function findAll(array $filters = []): array;
}
