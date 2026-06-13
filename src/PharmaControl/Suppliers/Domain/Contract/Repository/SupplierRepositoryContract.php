<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\Contract\Repository;

use PharmaControl\Suppliers\Domain\Model\Supplier;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;

interface SupplierRepositoryContract
{
    public function save(Supplier $supplier): void;

    public function findById(SupplierId $id): ?Supplier;

    public function findByRfc(Rfc $rfc): ?Supplier;

    /**
     * @param array{
     *   search?:    string,
     *   type?:      string,
     *   is_active?: bool,
     *   per_page?:  int,
     *   page?:      int,
     * } $filters
     * @return array{
     *   data:         Supplier[],
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     * }
     */
    public function findAll(array $filters = []): array;
}
