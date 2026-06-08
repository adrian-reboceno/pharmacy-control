<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Eloquent/Repository/EloquentAuditLogRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\Model\AuditEntry;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentAuditLog;
use PharmaControl\Shared\ValueObject\BranchId;

final class EloquentAuditLogRepository implements AuditLogRepositoryContract
{
    public function append(AuditEntry $entry): void
    {
        EloquentAuditLog::create([
            'id' => $entry->id ?: bin2hex(random_bytes(16)),
            'user_id' => $entry->userId->value,
            'user_email' => $entry->userEmail,
            'user_role' => $entry->userRole,
            'branch_id' => $entry->branchId?->value,
            'module' => $entry->module,
            'action' => $entry->action,
            'entity_type' => $entry->entityType,
            'entity_id' => $entry->entityId,
            'old_values' => $entry->oldValues ? json_encode($entry->oldValues) : null,
            'new_values' => $entry->newValues ? json_encode($entry->newValues) : null,
            'metadata' => $entry->metadata ? json_encode($entry->metadata) : null,
            'ip_address' => $entry->ipAddress->value,
            'user_agent' => $entry->userAgent,
            'status' => $entry->status,
            'timestamp' => $entry->timestamp->format('Y-m-d H:i:s'),
        ]);
    }

    /** @return list<AuditEntry> */
    public function findByUser(UserId $userId, int $limit, int $offset): array
    {
        return EloquentAuditLog::where('user_id', $userId->value)
            ->orderByDesc('timestamp')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->values()
            ->all();
    }

    /** @return list<AuditEntry> */
    public function findByModule(string $module, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return EloquentAuditLog::where('module', $module)
            ->whereBetween('timestamp', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->orderByDesc('timestamp')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->values()
            ->all();
    }

    private function toDomain(EloquentAuditLog $model): AuditEntry
    {
        return new AuditEntry(
            id: $model->id,
            userId: new UserId($model->user_id),
            userEmail: $model->user_email,
            userRole: $model->user_role,
            branchId: $model->branch_id ? new BranchId($model->branch_id) : null,
            module: $model->module,
            action: $model->action,
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            oldValues: $model->old_values,
            newValues: $model->new_values,
            metadata: $model->metadata,
            ipAddress: new IpAddress($model->ip_address),
            userAgent: $model->user_agent,
            status: $model->status,
            timestamp: $model->timestamp instanceof \DateTimeImmutable
                ? $model->timestamp
                : new \DateTimeImmutable($model->timestamp),
        );
    }
}
