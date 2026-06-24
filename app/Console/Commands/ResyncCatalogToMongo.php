<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PharmaControl\Catalog\ActiveIngredient\Application\DTO\IngredientDTO;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Shared\Infrastructure\Mongo\MongoCatalogSyncService;
use PharmaControl\Suppliers\Application\DTO\SupplierDTO;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;

final class ResyncCatalogToMongo extends Command
{
    protected $signature = 'catalog:resync
                            {--module= : Solo este módulo (laboratories, classifications, categories, units, presentations, routes, statuses, ingredients, suppliers). Sin esta opción, resincroniza todos.}
                            {--truncate : Vacía la colección Mongo antes de reconstruirla}';

    protected $description = 'Reconstruye las colecciones MongoDB de catálogos leyendo el estado completo desde PostgreSQL';

    public function __construct(
        private readonly MongoCatalogSyncService $mongo,
        private readonly LaboratoryRepositoryContract $laboratories,
        private readonly ClassificationRepositoryContract $classifications,
        private readonly CategoryRepositoryContract $categories,
        private readonly UnitRepositoryContract $units,
        private readonly PresentationRepositoryContract $presentations,
        private readonly RouteRepositoryContract $routes,
        private readonly StatusRepositoryContract $statuses,
        private readonly IngredientRepositoryContract $ingredients,
        private readonly SupplierRepositoryContract $suppliers,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $module = $this->option('module');
        $truncate = (bool) $this->option('truncate');

        $modules = $module !== null
            ? [$module]
            : ['laboratories', 'classifications', 'categories', 'units', 'presentations', 'routes', 'statuses', 'ingredients', 'suppliers'];

        foreach ($modules as $m) {
            $this->resyncModule($m, $truncate);
        }

        $this->info('Resincronización completa.');

        return self::SUCCESS;
    }

    private function resyncModule(string $module, bool $truncate): void
    {
        $this->info("Resincronizando: {$module}...");

        [$collection, $records] = match ($module) {
            'laboratories' => ['laboratories', $this->fetchAll(
                $this->laboratories,
                LaboratoryDTO::class,
                fn (LaboratoryDTO $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'country_code' => $dto->countryCode,
                    'website' => $dto->website,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            'classifications' => ['classifications', $this->fetchAll(
                $this->classifications,
                ClassificationDTO::class,
                fn (ClassificationDTO $dto) => [
                    'id' => $dto->id,
                    'lgs_group' => $dto->lgsGroup,
                    'lgs_group_label' => $dto->lgsGroupLabel,
                    'name' => $dto->name,
                    'prescription_type' => $dto->prescriptionType,
                    'prescription_type_label' => $dto->prescriptionTypeLabel,
                    'validity_days' => $dto->validityDays,
                    'validity_note' => $dto->validityNote,
                    'is_controlled' => $dto->isControlled,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            // FIX: CategoryRepositoryContract NO tiene findAll() — su firma
            // real es findAllFlat(array $filters = []): Category[], que
            // devuelve directamente un array de entidades (no la estructura
            // paginada {data, total, ...} que sí tienen los demás módulos).
            // Por eso usa su propio método fetchAllCategories() en vez del
            // fetchAll() genérico.
            'categories' => ['categories', $this->fetchAllCategories()],

            'units' => ['units_of_measurement', $this->fetchAll(
                $this->units,
                UnitDTO::class,
                fn (UnitDTO $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'symbol' => $dto->symbol,
                    'type' => $dto->type,
                    'type_label' => $dto->typeLabel,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            'presentations' => ['presentations', $this->fetchAll(
                $this->presentations,
                PresentationDTO::class,
                fn (PresentationDTO $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'abbreviation' => $dto->abbreviation,
                    'description' => $dto->description,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            'routes' => ['routes_of_administration', $this->fetchAll(
                $this->routes,
                RouteDTO::class,
                fn (RouteDTO $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'code' => $dto->code,
                    'description' => $dto->description,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            'statuses' => ['product_statuses', $this->fetchAll(
                $this->statuses,
                StatusDTO::class,
                fn (StatusDTO $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'code' => $dto->code,
                    'description' => $dto->description,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            'ingredients' => ['active_ingredients', $this->fetchAll(
                $this->ingredients,
                IngredientDTO::class,
                fn (IngredientDTO $dto) => [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'dci_code' => $dto->dciCode,
                    'cas_number' => $dto->casNumber,
                    'description' => $dto->description,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            'suppliers' => ['suppliers', $this->fetchAll(
                $this->suppliers,
                SupplierDTO::class,
                fn (SupplierDTO $dto) => [
                    'id' => $dto->id,
                    'type' => $dto->type,
                    'type_label' => $dto->typeLabel,
                    'rfc' => $dto->rfc,
                    'legal_name' => $dto->legalName,
                    'trade_name' => $dto->tradeName,
                    'address' => [
                        'street' => $dto->address->street,
                        'ext_number' => $dto->address->extNumber,
                        'int_number' => $dto->address->intNumber,
                        'neighborhood' => $dto->address->neighborhood,
                        'municipality' => $dto->address->municipality,
                        'state' => $dto->address->state,
                        'postal_code' => $dto->address->postalCode,
                        'country' => $dto->address->country,
                    ],
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                    'is_active' => $dto->isActive,
                    'created_by' => $dto->createdBy,
                    'created_at' => $dto->createdAt,
                    'updated_at' => $dto->updatedAt,
                ]
            )],

            default => throw new \InvalidArgumentException("Módulo desconocido: {$module}"),
        };

        if ($truncate) {
            $this->mongo->truncate($collection);
            $this->line("  → Colección {$collection} vaciada.");
        }

        $count = 0;
        foreach ($records as $doc) {
            $this->mongo->upsert($collection, $doc['id'], $doc);
            $count++;
        }

        $this->mongo->ensureIndexes($collection, ['is_active', 'name']);

        $this->line("  → {$count} documentos sincronizados en '{$collection}'.");
    }

    /**
     * @param  class-string  $dtoClass
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(object $repository, string $dtoClass, \Closure $toArray): array
    {
        $result = $repository->findAll(['per_page' => 10000, 'page' => 1]);

        return array_map(
            fn ($entity) => $toArray($dtoClass::fromDomain($entity)),
            $result['data']
        );
    }

    /**
     * Categories tiene una firma de repositorio distinta al resto:
     * findAllFlat() devuelve directamente Category[] (lista plana del árbol
     * completo), sin la envoltura paginada {data, total, ...} que usan los
     * demás módulos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllCategories(): array
    {
        $entities = $this->categories->findAllFlat();

        return array_map(function ($category) {
            $dto = CategoryDTO::fromDomain($category);

            return [
                'id' => $dto->id,
                'parent_id' => $dto->parentId,
                'name' => $dto->name,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
                'is_root' => $dto->isRoot,
                'created_by' => $dto->createdBy,
                'created_at' => $dto->createdAt,
                'updated_at' => $dto->updatedAt,
            ];
        }, $entities);
    }
}
