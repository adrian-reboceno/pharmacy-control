<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\CatalogSync\SyncCategoryListener;
use App\Listeners\CatalogSync\SyncClassificationListener;
use App\Listeners\CatalogSync\SyncIngredientListener;
use App\Listeners\CatalogSync\SyncLaboratoryListener;
use App\Listeners\CatalogSync\SyncLocationListener;
use App\Listeners\CatalogSync\SyncPresentationListener;
use App\Listeners\CatalogSync\SyncProductListener;
use App\Listeners\CatalogSync\SyncRouteListener;
use App\Listeners\CatalogSync\SyncStatusListener;
use App\Listeners\CatalogSync\SyncSupplierListener;
use App\Listeners\CatalogSync\SyncUnitListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientCreated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientDeactivated;
use PharmaControl\Catalog\ActiveIngredient\Domain\Event\IngredientUpdated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryCreated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryDeactivated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryUpdated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationCreated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationDeactivated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationUpdated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryCreated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryDeactivated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryUpdated;
use PharmaControl\Catalog\Location\Domain\Event\LocationCreated;
use PharmaControl\Catalog\Location\Domain\Event\LocationDeactivated;
use PharmaControl\Catalog\Location\Domain\Event\LocationUpdated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationCreated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationDeactivated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationUpdated;
use PharmaControl\Catalog\Products\Domain\Event\ProductCreated;
use PharmaControl\Catalog\Products\Domain\Event\ProductDeactivated;
use PharmaControl\Catalog\Products\Domain\Event\ProductImageAdded;
use PharmaControl\Catalog\Products\Domain\Event\ProductUpdated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteCreated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteDeactivated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteUpdated;
use PharmaControl\Catalog\Status\Domain\Event\StatusCreated;
use PharmaControl\Catalog\Status\Domain\Event\StatusDeactivated;
use PharmaControl\Catalog\Status\Domain\Event\StatusUpdated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitCreated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitDeactivated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitUpdated;
use PharmaControl\Suppliers\Domain\Event\SupplierCreated;
use PharmaControl\Suppliers\Domain\Event\SupplierDeactivated;
use PharmaControl\Suppliers\Domain\Event\SupplierUpdated;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, list<array{0: class-string, 1: string}|class-string>> */
    protected $listen = [
        // ── Laboratories ──
        LaboratoryCreated::class => [
            [SyncLaboratoryListener::class, 'handleCreated'],
        ],
        LaboratoryUpdated::class => [
            [SyncLaboratoryListener::class, 'handleUpdated'],
        ],
        LaboratoryDeactivated::class => [
            [SyncLaboratoryListener::class, 'handleDeactivated'],
        ],

        // ── Classifications ──
        ClassificationCreated::class => [
            [SyncClassificationListener::class, 'handleCreated'],
        ],
        ClassificationUpdated::class => [
            [SyncClassificationListener::class, 'handleUpdated'],
        ],
        ClassificationDeactivated::class => [
            [SyncClassificationListener::class, 'handleDeactivated'],
        ],

        // ── Categories ──
        CategoryCreated::class => [
            [SyncCategoryListener::class, 'handleCreated'],
        ],
        CategoryUpdated::class => [
            [SyncCategoryListener::class, 'handleUpdated'],
        ],
        CategoryDeactivated::class => [
            [SyncCategoryListener::class, 'handleDeactivated'],
        ],

        // ── UnitOfMeasurement ──
        UnitCreated::class => [
            [SyncUnitListener::class, 'handleCreated'],
        ],
        UnitUpdated::class => [
            [SyncUnitListener::class, 'handleUpdated'],
        ],
        UnitDeactivated::class => [
            [SyncUnitListener::class, 'handleDeactivated'],
        ],

        // ── Presentations ──
        PresentationCreated::class => [
            [SyncPresentationListener::class, 'handleCreated'],
        ],
        PresentationUpdated::class => [
            [SyncPresentationListener::class, 'handleUpdated'],
        ],
        PresentationDeactivated::class => [
            [SyncPresentationListener::class, 'handleDeactivated'],
        ],

        // ── RoutesOfAdministration ──
        RouteCreated::class => [
            [SyncRouteListener::class, 'handleCreated'],
        ],
        RouteUpdated::class => [
            [SyncRouteListener::class, 'handleUpdated'],
        ],
        RouteDeactivated::class => [
            [SyncRouteListener::class, 'handleDeactivated'],
        ],

        // ── Status ──
        StatusCreated::class => [
            [SyncStatusListener::class, 'handleCreated'],
        ],
        StatusUpdated::class => [
            [SyncStatusListener::class, 'handleUpdated'],
        ],
        StatusDeactivated::class => [
            [SyncStatusListener::class, 'handleDeactivated'],
        ],

        // ── ActiveIngredient ──
        IngredientCreated::class => [
            [SyncIngredientListener::class, 'handleCreated'],
        ],
        IngredientUpdated::class => [
            [SyncIngredientListener::class, 'handleUpdated'],
        ],
        IngredientDeactivated::class => [
            [SyncIngredientListener::class, 'handleDeactivated'],
        ],

        // ── Location ──
        LocationCreated::class => [
            [SyncLocationListener::class, 'handleCreated'],
        ],
        LocationUpdated::class => [
            [SyncLocationListener::class, 'handleUpdated'],
        ],
        LocationDeactivated::class => [
            [SyncLocationListener::class, 'handleDeactivated'],
        ],

        // ── Products ──
        ProductCreated::class => [
            [SyncProductListener::class, 'handleCreated'],
        ],
        ProductUpdated::class => [
            [SyncProductListener::class, 'handleUpdated'],
        ],
        ProductDeactivated::class => [
            [SyncProductListener::class, 'handleDeactivated'],
        ],
        ProductImageAdded::class => [
            [SyncProductListener::class, 'handleImageAdded'],
        ],

        // ── Suppliers ──
        SupplierCreated::class => [
            [SyncSupplierListener::class, 'handleCreated'],
        ],
        SupplierUpdated::class => [
            [SyncSupplierListener::class, 'handleUpdated'],
        ],
        SupplierDeactivated::class => [
            [SyncSupplierListener::class, 'handleDeactivated'],
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
