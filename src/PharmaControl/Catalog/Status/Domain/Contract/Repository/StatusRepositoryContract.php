<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\Contract\Repository;

use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;

interface StatusRepositoryContract
{
    public function save(ProductStatus $status): void;

    public function findById(StatusId $id): ?ProductStatus;

    public function findByName(StatusName $name): ?ProductStatus;

    public function findByCode(StatusCode $code): ?ProductStatus;

    /**
     * @param array{
     *   search?:    string,
     *   is_active?: bool,
     *   per_page?:  int,
     *   page?:      int,
     * } $filters
     * @return array{
     *   data:         ProductStatus[],
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     * }
     */
    public function findAll(array $filters = []): array;
}
