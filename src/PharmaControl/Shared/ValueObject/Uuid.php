<?php

// ── ARCHIVO: src/PharmaControl/Shared/ValueObject/Uuid.php ──
declare(strict_types=1);

namespace PharmaControl\Shared\ValueObject;

abstract readonly class Uuid
{
    public function __construct(public readonly string $value)
    {
        /*  if (!preg_match(
             '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
             $value
         )) {
             throw new \InvalidArgumentException("UUID v4 inválido: {$value}");
         } */
        if (! preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[47][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        )) {
            throw new \InvalidArgumentException("UUID inválido (se esperaba v4 o v7): {$value}");
        }
    }

    public static function generate(): static
    {
        /*  $data = random_bytes(16);
         $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
         $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

         return new static(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4))); */

        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x40); // Setea la versión 4
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80); // Setea la variante

        return new static(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
