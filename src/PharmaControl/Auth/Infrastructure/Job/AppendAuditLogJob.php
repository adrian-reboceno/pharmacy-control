<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Job/AppendAuditLogJob.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\Model\AuditEntry;

final class AppendAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 10;

    public function __construct(
        private readonly AuditEntry $entry,
    ) {
        $this->onQueue('audit');
    }

    public function handle(AuditLogRepositoryContract $auditLog): void
    {
        $auditLog->append($this->entry);
    }
}
