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
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Infrastructure\Persistence\Eloquent\Repository\EloquentCategoryRepository;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Infrastructure\Persistence\Eloquent\Repository\EloquentClassificationRepository;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Infrastructure\Persistence\Eloquent\Repository\EloquentLaboratoryRepository;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Eloquent\Repository\EloquentPresentationRepository;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Eloquent\Repository\EloquentRouteRepository;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Eloquent\Repository\EloquentUnitRepository;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Infrastructure\Persistence\Eloquent\Repository\EloquentStatusRepository;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use PharmaControl\Suppliers\Infrastructure\Persistence\Eloquent\Repository\EloquentSupplierRepository;

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

        // Catalog — Laboratories
        $this->app->bind(LaboratoryRepositoryContract::class, EloquentLaboratoryRepository::class);

        // Catalog — Classifications
        $this->app->bind(ClassificationRepositoryContract::class, EloquentClassificationRepository::class);

        // Catalog — Categories
        $this->app->bind(CategoryRepositoryContract::class, EloquentCategoryRepository::class);

        // Catalog — Units of Measurement
        $this->app->bind(UnitRepositoryContract::class, EloquentUnitRepository::class);

        // Catalog — Presentations
        $this->app->bind(PresentationRepositoryContract::class, EloquentPresentationRepository::class);

        // Catalog — Routes of Administration
        $this->app->bind(RouteRepositoryContract::class, EloquentRouteRepository::class);

        // Catalog — Status
        $this->app->bind(StatusRepositoryContract::class, EloquentStatusRepository::class);

        // Suppliers
        $this->app->bind(SupplierRepositoryContract::class, EloquentSupplierRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
