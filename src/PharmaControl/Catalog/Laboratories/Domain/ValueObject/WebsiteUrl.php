<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/ValueObject/WebsiteUrl.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\ValueObject;

final readonly class WebsiteUrl
{
    public function __construct(public readonly string $value)
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("URL inválida: {$value}");
        }
        if (! str_starts_with($value, 'https://')) {
            throw new \InvalidArgumentException('La URL debe iniciar con https://');
        }
        if (mb_strlen($value) > 500) {
            throw new \InvalidArgumentException('La URL no puede exceder 500 caracteres.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
