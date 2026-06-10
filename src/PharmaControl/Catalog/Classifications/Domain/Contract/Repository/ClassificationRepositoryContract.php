<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/Contract/Repository/ClassificationRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\Contract\Repository;

use PharmaControl\Catalog\Classifications\Domain\Model\MedicationClassification;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;

interface ClassificationRepositoryContract
{
    public function save(MedicationClassification $classification): void;

    public function findById(ClassificationId $id): ?MedicationClassification;

    public function findByLgsGroup(LgsGroup $group): ?MedicationClassification;

    /**
     * @param array{
     *   is_active?:     bool,
     *   is_controlled?: bool,
     *   per_page?:      int,
     *   page?:          int,
     * } $filters
     * @return array{
     *   data:          MedicationClassification[],
     *   total:         int,
     *   per_page:      int,
     *   current_page:  int,
     *   last_page:     int,
     * }
     */
    public function findAll(array $filters = []): array;
}
