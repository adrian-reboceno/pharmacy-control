<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\ValueObject;

enum SupplierType: string
{
    case MORAL  = 'MORAL';
    case FISICA = 'FISICA';

    public function label(): string
    {
        return match ($this) {
            self::MORAL  => 'Persona Moral',
            self::FISICA => 'Persona Física',
        };
    }

    public function rfcLength(): int
    {
        return match ($this) {
            self::MORAL  => 12,
            self::FISICA => 13,
        };
    }
}
