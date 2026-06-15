<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\ValueObject;

use Illuminate\Support\Str;

final readonly class StatusId
{
    public function __construct(public readonly string $value)
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw new \InvalidArgumentException("StatusId inválido: {$value}");
        }
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
