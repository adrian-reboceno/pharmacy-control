<?php

// ── ARCHIVO: app/Providers/AppServiceProvider.php ──
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PharmaControl\Auth\Domain\Contract\Repository\AuditLogRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\PasswordHistoryRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\RoleRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\SessionRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Repository\UserRepositoryContract;
use PharmaControl\Auth\Domain\Contract\Service\CacheServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\Contract\Service\NotificationServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\RateLimiterServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;
use PharmaControl\Auth\Domain\Contract\Service\TwoFactorServiceContract;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository\EloquentAuditLogRepository;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository\EloquentPasswordHistoryRepository;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository\EloquentRoleRepository;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository\EloquentSessionRepository;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Repository\EloquentUserRepository;
use PharmaControl\Auth\Infrastructure\Service\LaravelNotificationService;
use PharmaControl\Auth\Infrastructure\Service\LaravelQueueEventPublisher;
use PharmaControl\Auth\Infrastructure\Service\OtphpTwoFactorService;
use PharmaControl\Auth\Infrastructure\Service\RedisCacheService;
use PharmaControl\Auth\Infrastructure\Service\RedisRateLimiterService;
use PharmaControl\Auth\Infrastructure\Service\SanctumTokenService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(UserRepositoryContract::class, EloquentUserRepository::class);
        $this->app->bind(SessionRepositoryContract::class, EloquentSessionRepository::class);
        $this->app->bind(RoleRepositoryContract::class, EloquentRoleRepository::class);
        $this->app->bind(AuditLogRepositoryContract::class, EloquentAuditLogRepository::class);
        $this->app->bind(PasswordHistoryRepositoryContract::class, EloquentPasswordHistoryRepository::class);

        // Service bindings
        $this->app->bind(TokenServiceContract::class, SanctumTokenService::class);
        $this->app->bind(TwoFactorServiceContract::class, OtphpTwoFactorService::class);
        $this->app->bind(NotificationServiceContract::class, LaravelNotificationService::class);
        $this->app->bind(RateLimiterServiceContract::class, RedisRateLimiterService::class);
        $this->app->bind(CacheServiceContract::class, RedisCacheService::class);
        $this->app->bind(EventPublisherContract::class, LaravelQueueEventPublisher::class);
    }

    public function boot(): void
    {
        //
    }
}
