<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Service/EventPublisherContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Service;

use PharmaControl\Shared\Event\DomainEvent;

interface EventPublisherContract
{
    public function publish(DomainEvent $event): void;
}
