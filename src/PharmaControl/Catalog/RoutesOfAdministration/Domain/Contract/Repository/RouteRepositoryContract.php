<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository;

use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;

interface RouteRepositoryContract
{
    public function save(RouteOfAdministration $route): void;

    public function findById(RouteId $id): ?RouteOfAdministration;

    public function findByName(RouteName $name): ?RouteOfAdministration;

    public function findByCode(RouteCode $code): ?RouteOfAdministration;

    /**
     * @param array{
     *   search?:    string,
     *   is_active?: bool,
     *   per_page?:  int,
     *   page?:      int,
     * } $filters
     * @return array{
     *   data:         RouteOfAdministration[],
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     * }
     */
    public function findAll(array $filters = []): array;
}
