<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Contract\Repository;

use PharmaControl\Catalog\Products\Domain\Model\Product;
use PharmaControl\Catalog\Products\Domain\ValueObject\Barcode;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;

interface ProductRepositoryContract
{
    public function save(Product $product): void;

    public function findById(ProductId $id): ?Product;

    public function findByName(ProductName $name): ?Product;

    public function findByBarcode(Barcode $barcode): ?Product;

    /**
     * @param array{
     *   search?:        string|null,
     *   type?:          string|null,
     *   status_id?:     string|null,
     *   category_id?:   string|null,
     *   laboratory_id?: string|null,
     *   sale_condition?: string|null,
     *   manage_lots?:   bool|null,
     *   is_active?:     bool|null,
     *   per_page?:      int,
     *   page?:          int,
     * } $filters
     * @return array{
     *   data:         Product[],
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     * }
     */
    public function findAll(array $filters = []): array;
}
