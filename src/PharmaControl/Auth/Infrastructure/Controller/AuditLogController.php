<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Controller/AuditLogController.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Controller;

use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class AuditLogController
{
    public function __construct(
        private readonly AuditLogRepositoryContract $auditLog,
    ) {}

    public function index(array $data): array
    {
        $userId = new UserId($data['user_id']);
        $limit = (int) ($data['limit'] ?? 50);
        $offset = (int) ($data['offset'] ?? 0);

        $entries = $this->auditLog->findByUser($userId, $limit, $offset);

        return array_map(fn ($entry) => [
            'id' => $entry->id,
            'module' => $entry->module,
            'action' => $entry->action,
            'entity_type' => $entry->entityType,
            'entity_id' => $entry->entityId,
            'ip_address' => $entry->ipAddress->value,
            'status' => $entry->status,
            'timestamp' => $entry->timestamp->format('Y-m-d H:i:s'),
        ], $entries);
    }
}
