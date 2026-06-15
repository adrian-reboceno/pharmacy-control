<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\Exception;

final class DuplicateStatusCodeException extends \DomainException
{
    public function __construct(string $code)
    {
        parent::__construct("Ya existe un estado con el código: {$code}");
    }
}
