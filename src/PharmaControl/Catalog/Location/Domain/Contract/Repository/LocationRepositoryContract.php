<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Domain\Contract\Repository;

use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Domain\Model\Location;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;

interface LocationRepositoryContract
{
    public function save(Location $location): void;

    public function findById(LocationId $id): ?Location;

    public function findByIdAsDTO(LocationId $id): ?LocationDTO;

    /** Busca por nombre (case-insensitive) entre hijos directos del padre dado (null = raíces). */
    public function findByNameAndParent(LocationName $name, ?LocationId $parentId): ?Location;

    /**
     * Devuelve todos los hijos directos de un nodo.
     *
     * @return Location[]
     */
    public function findChildren(LocationId $parentId): array;

    /**
     * Lista plana de todos los nodos con filtros opcionales.
     *
     * @return LocationDTO[]
     */
    public function findAllFlat(array $filters = []): array;

    /**
     * Solo las Posiciones (level=4) activas — para el selector de asignación de producto.
     *
     * @return LocationDTO[]
     */
    public function findLeaves(array $filters = []): array;
}
