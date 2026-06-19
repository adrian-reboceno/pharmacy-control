<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, list<array{0: class-string, 1: string}|class-string>> */
    protected $listen = [
        // ── Laboratories ──
        \PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryCreated::class => [
            [\App\Listeners\CatalogSync\SyncLaboratoryListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryUpdated::class => [
            [\App\Listeners\CatalogSync\SyncLaboratoryListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncLaboratoryListener::class, 'handleDeactivated'],
        ],

        // ── Classifications ──
        \PharmaControl\Catalog\Classifications\Domain\Event\ClassificationCreated::class => [
            [\App\Listeners\CatalogSync\SyncClassificationListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\Classifications\Domain\Event\ClassificationUpdated::class => [
            [\App\Listeners\CatalogSync\SyncClassificationListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\Classifications\Domain\Event\ClassificationDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncClassificationListener::class, 'handleDeactivated'],
        ],

        // ── Categories ──
        \PharmaControl\Catalog\Categories\Domain\Event\CategoryCreated::class => [
            [\App\Listeners\CatalogSync\SyncCategoryListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\Categories\Domain\Event\CategoryUpdated::class => [
            [\App\Listeners\CatalogSync\SyncCategoryListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\Categories\Domain\Event\CategoryDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncCategoryListener::class, 'handleDeactivated'],
        ],

        // ── UnitOfMeasurement ──
        \PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitCreated::class => [
            [\App\Listeners\CatalogSync\SyncUnitListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitUpdated::class => [
            [\App\Listeners\CatalogSync\SyncUnitListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncUnitListener::class, 'handleDeactivated'],
        ],

        // ── Presentations ──
        \PharmaControl\Catalog\Presentations\Domain\Event\PresentationCreated::class => [
            [\App\Listeners\CatalogSync\SyncPresentationListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\Presentations\Domain\Event\PresentationUpdated::class => [
            [\App\Listeners\CatalogSync\SyncPresentationListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\Presentations\Domain\Event\PresentationDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncPresentationListener::class, 'handleDeactivated'],
        ],

        // ── RoutesOfAdministration ──
        \PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteCreated::class => [
            [\App\Listeners\CatalogSync\SyncRouteListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteUpdated::class => [
            [\App\Listeners\CatalogSync\SyncRouteListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncRouteListener::class, 'handleDeactivated'],
        ],

        // ── Status ──
        \PharmaControl\Catalog\Status\Domain\Event\StatusCreated::class => [
            [\App\Listeners\CatalogSync\SyncStatusListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\Status\Domain\Event\StatusUpdated::class => [
            [\App\Listeners\CatalogSync\SyncStatusListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\Status\Domain\Event\StatusDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncStatusListener::class, 'handleDeactivated'],
        ],

        // ── ActiveIngredient ──
        \PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientCreated::class => [
            [\App\Listeners\CatalogSync\SyncIngredientListener::class, 'handleCreated'],
        ],
        \PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientUpdated::class => [
            [\App\Listeners\CatalogSync\SyncIngredientListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncIngredientListener::class, 'handleDeactivated'],
        ],

        // ── Suppliers ──
        \PharmaControl\Suppliers\Domain\Event\SupplierCreated::class => [
            [\App\Listeners\CatalogSync\SyncSupplierListener::class, 'handleCreated'],
        ],
        \PharmaControl\Suppliers\Domain\Event\SupplierUpdated::class => [
            [\App\Listeners\CatalogSync\SyncSupplierListener::class, 'handleUpdated'],
        ],
        \PharmaControl\Suppliers\Domain\Event\SupplierDeactivated::class => [
            [\App\Listeners\CatalogSync\SyncSupplierListener::class, 'handleDeactivated'],
        ],
    ];

    /**
     * Desactiva el auto-discovery de eventos de Laravel.
     *
     * Sin esto, Laravel escanea app/Listeners/ buscando métodos handle*
     * por convención de nombres y los registra automáticamente —
     * duplicando cada listener ya declarado explícitamente arriba en
     * $listen. Esto causaba que cada evento de catálogo (LaboratoryCreated,
     * etc.) disparara su Job de sincronización a Mongo DOS veces por cada
     * operación real (visible en Horizon: dos entradas idénticas en
     * Completed Jobs por cada laboratorio creado).
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}