<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class IngredientCreated implements DomainEvent
{
    public function __construct(
        public readonly IngredientId $id,
        public readonly IngredientName $name,
        public readonly DciCode $dciCode,
        public readonly ?CasNumber $casNumber,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
