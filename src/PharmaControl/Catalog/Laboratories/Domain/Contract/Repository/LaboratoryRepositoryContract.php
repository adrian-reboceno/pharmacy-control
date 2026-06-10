<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/Contract/Repository/LaboratoryRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\Contract\Repository;

use PharmaControl\Catalog\Laboratories\Domain\Model\Laboratory;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;

interface LaboratoryRepositoryContract
{
    public function save(Laboratory $laboratory): void;

    public function findById(LaboratoryId $id): ?Laboratory;

    public function findByName(LaboratoryName $name): ?Laboratory;

    /**
     * @param array{
     *   search?:       string,
     *   country_code?: string,
     *   is_active?:    bool,
     *   per_page?:     int,
     *   page?:         int,
     * } $filters
     * @return array{
     *   data:          Laboratory[],
     *   total:         int,
     *   per_page:      int,
     *   current_page:  int,
     *   last_page:     int,
     * }
     */
    public function findAll(array $filters = []): array;
}
