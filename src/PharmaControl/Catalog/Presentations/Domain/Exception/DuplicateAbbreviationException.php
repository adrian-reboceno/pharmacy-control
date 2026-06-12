<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Domain\Exception;

final class DuplicateAbbreviationException extends \DomainException
{
    public function __construct(string $abbreviation)
    {
        parent::__construct("Ya existe una presentación con la abreviatura: {$abbreviation}");
    }
}
