<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/ValueObject/CategorySlug.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\ValueObject;

final readonly class CategorySlug
{
    public function __construct(public readonly string $value)
    {
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $value)) {
            throw new \InvalidArgumentException("Slug inválido: {$value}. Solo letras minúsculas, números y guiones.");
        }
        if (mb_strlen($value) > 160) {
            throw new \InvalidArgumentException('El slug no puede exceder 160 caracteres.');
        }
    }

    public static function generate(CategoryName $name): self
    {
        $slug = mb_strtolower($name->value);

        $translit = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c', '/' => '-', '&' => 'y',
        ];
        $slug = strtr($slug, $translit);

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = preg_replace('/-{2,}/', '-', $slug);

        return new self($slug);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
