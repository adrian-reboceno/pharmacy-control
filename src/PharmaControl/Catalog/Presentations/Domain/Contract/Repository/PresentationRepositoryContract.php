<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\Contract\Repository;

use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;

interface PresentationRepositoryContract
{
    public function save(Presentation $presentation): void;

    public function findById(PresentationId $id): ?Presentation;

    public function findByName(PresentationName $name): ?Presentation;

    public function findByAbbreviation(Abbreviation $abbreviation): ?Presentation;

    /**
     * @param array{
     *   search?:    string,
     *   is_active?: bool,
     *   per_page?:  int,
     *   page?:      int,
     * } $filters
     *
     * @return array{
     *   data:         Presentation[],
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     * }
     */
    public function findAll(array $filters = []): array;
}
