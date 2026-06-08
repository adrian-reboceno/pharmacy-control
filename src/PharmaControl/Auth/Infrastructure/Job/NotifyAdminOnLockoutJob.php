<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Job/NotifyAdminOnLockoutJob.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\ValueObject\IpAddress;
use PharmaControl\Auth\Domain\ValueObject\UserId;

final class NotifyAdminOnLockoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly string $userId,
        private readonly string $ipAddress,
        private readonly string $lockedUntil,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        UserRepositoryContract $users,
        NotificationServiceContract $notifications,
    ): void {
        $user = $users->findById(new UserId($this->userId));
        if ($user === null) {
            return;
        }

        $notifications->sendLockNotification(
            $user->id,
            $user->email,
            new IpAddress($this->ipAddress),
            new \DateTimeImmutable($this->lockedUntil),
        );
    }
}
