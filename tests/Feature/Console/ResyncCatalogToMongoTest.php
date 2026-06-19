<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Commands\ResyncCatalogToMongo;
use Illuminate\Support\Facades\Artisan;
use PharmaControl\Catalog\ActiveIngredient\Domain\Contract\Repository\IngredientRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Shared\Infrastructure\Mongo\MongoCatalogSyncService;
use PharmaControl\Suppliers\Domain\Contract\Repository\SupplierRepositoryContract;
use Tests\TestCase;

class ResyncCatalogToMongoTest extends TestCase
{
    /** @test */
    public function it_resyncs_all_modules_when_no_module_option_given(): void
    {
        $mongo = $this->createMock(MongoCatalogSyncService::class);
        $mongo->expects($this->exactly(9))->method('ensureIndexes');
        $mongo->expects($this->never())->method('truncate');

        $this->bindEmptyRepositories($mongo);

        $this->app->instance(MongoCatalogSyncService::class, $mongo);

        $exitCode = Artisan::call('catalog:resync');

        $this->assertSame(0, $exitCode);
    }

    /** @test */
    public function it_resyncs_only_specified_module_with_module_option(): void
    {
        $mongo = $this->createMock(MongoCatalogSyncService::class);
        $mongo->expects($this->once())->method('ensureIndexes')->with('laboratories', $this->anything());
        $mongo->expects($this->never())->method('truncate');

        $labRepo = $this->createMock(LaboratoryRepositoryContract::class);
        $labRepo->method('findAll')->willReturn(['data' => [], 'total' => 0, 'per_page' => 10000, 'current_page' => 1, 'last_page' => 1]);

        $this->app->instance(MongoCatalogSyncService::class, $mongo);
        $this->app->instance(LaboratoryRepositoryContract::class, $labRepo);

        $exitCode = Artisan::call('catalog:resync', ['--module' => 'laboratories']);

        $this->assertSame(0, $exitCode);
    }

    /** @test */
    public function it_truncates_collection_before_resync_when_truncate_flag_is_set(): void
    {
        $mongo = $this->createMock(MongoCatalogSyncService::class);
        $mongo->expects($this->once())->method('truncate')->with('laboratories');
        $mongo->expects($this->once())->method('ensureIndexes');

        $labRepo = $this->createMock(LaboratoryRepositoryContract::class);
        $labRepo->method('findAll')->willReturn(['data' => [], 'total' => 0, 'per_page' => 10000, 'current_page' => 1, 'last_page' => 1]);

        $this->app->instance(MongoCatalogSyncService::class, $mongo);
        $this->app->instance(LaboratoryRepositoryContract::class, $labRepo);

        $exitCode = Artisan::call('catalog:resync', ['--module' => 'laboratories', '--truncate' => true]);

        $this->assertSame(0, $exitCode);
    }

    /** @test */
    public function it_does_not_truncate_when_truncate_flag_is_absent(): void
    {
        $mongo = $this->createMock(MongoCatalogSyncService::class);
        $mongo->expects($this->never())->method('truncate');

        $labRepo = $this->createMock(LaboratoryRepositoryContract::class);
        $labRepo->method('findAll')->willReturn(['data' => [], 'total' => 0, 'per_page' => 10000, 'current_page' => 1, 'last_page' => 1]);

        $this->app->instance(MongoCatalogSyncService::class, $mongo);
        $this->app->instance(LaboratoryRepositoryContract::class, $labRepo);

        Artisan::call('catalog:resync', ['--module' => 'laboratories']);

        // La aserción es implícita: expects(never) lanzaría si se llamara
    }

    /** @test */
    public function it_creates_indexes_after_resync(): void
    {
        $mongo = $this->createMock(MongoCatalogSyncService::class);
        $mongo->expects($this->once())
            ->method('ensureIndexes')
            ->with('laboratories', $this->containsEqual('is_active'));

        $labRepo = $this->createMock(LaboratoryRepositoryContract::class);
        $labRepo->method('findAll')->willReturn(['data' => [], 'total' => 0, 'per_page' => 10000, 'current_page' => 1, 'last_page' => 1]);

        $this->app->instance(MongoCatalogSyncService::class, $mongo);
        $this->app->instance(LaboratoryRepositoryContract::class, $labRepo);

        Artisan::call('catalog:resync', ['--module' => 'laboratories']);
    }

    /** @test */
    public function it_fails_with_invalid_module_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Módulo desconocido: inexistente');

        $mongo = $this->createMock(MongoCatalogSyncService::class);
        $this->app->instance(MongoCatalogSyncService::class, $mongo);

        $command = $this->app->make(ResyncCatalogToMongo::class);
        $command->setLaravel($this->app);

        // Invocamos directamente el método privado a través del comando para provocar la excepción
        $method = new \ReflectionMethod(ResyncCatalogToMongo::class, 'resyncModule');
        $method->setAccessible(true);
        $method->invoke($command, 'inexistente', false);
    }

    private function bindEmptyRepositories(MongoCatalogSyncService $mongo): void
    {
        $emptyResult = ['data' => [], 'total' => 0, 'per_page' => 10000, 'current_page' => 1, 'last_page' => 1];

        $repos = [
            LaboratoryRepositoryContract::class,
            ClassificationRepositoryContract::class,
            CategoryRepositoryContract::class,
            UnitRepositoryContract::class,
            PresentationRepositoryContract::class,
            RouteRepositoryContract::class,
            StatusRepositoryContract::class,
            IngredientRepositoryContract::class,
            SupplierRepositoryContract::class,
        ];

        foreach ($repos as $contract) {
            $mock = $this->createMock($contract);
            $mock->method('findAll')->willReturn($emptyResult);
            $this->app->instance($contract, $mock);
        }

        $this->app->instance(MongoCatalogSyncService::class, $mongo);
    }
}
