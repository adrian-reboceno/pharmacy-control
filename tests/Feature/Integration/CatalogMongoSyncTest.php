<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Jobs\CatalogSync\SyncLaboratoryToMongoJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests de integración que verifican el flujo completo API → Postgres → Evento → Job → Mongo.
 *
 * Requieren:
 *  - Base de datos PostgreSQL de test configurada (DB_CONNECTION=pgsql en phpunit.xml)
 *  - MongoDB de test disponible (MONGO_DSN configurado en phpunit.xml)
 *
 * Para ejecutar solo estos tests:
 *   php artisan test --filter CatalogMongoSyncTest
 *
 * Para omitirlos en CI sin Mongo:
 *   php artisan test --exclude-group integration
 */
class CatalogMongoSyncTest extends TestCase
{
    /** @test @group integration */
    public function creating_a_laboratory_via_api_dispatches_sync_job(): void
    {
        Queue::fake();

        $payload = [
            'name'         => 'Lab Integración Test',
            'country_code' => 'MX',
        ];

        $response = $this->postJson('/api/v1/catalog/laboratories', $payload);

        $response->assertStatus(201);

        Queue::assertPushedOn('catalog-sync', SyncLaboratoryToMongoJob::class, function (SyncLaboratoryToMongoJob $job) {
            $doc = (fn () => $this->document)->call($job);
            return $doc['name'] === 'Lab Integración Test'
                && $doc['country_code'] === 'MX'
                && $doc['is_active'] === true;
        });
    }

    /** @test @group integration */
    public function updating_a_laboratory_via_api_dispatches_sync_job_with_new_data(): void
    {
        Queue::fake();

        // Primero crea
        $create = $this->postJson('/api/v1/catalog/laboratories', [
            'name'         => 'Lab Original',
            'country_code' => 'MX',
        ]);
        $create->assertStatus(201);
        $id = $create->json('data.id');

        Queue::fake(); // Reset para capturar solo el update

        // Actualiza
        $this->patchJson("/api/v1/catalog/laboratories/{$id}", [
            'name'         => 'Lab Actualizado',
            'country_code' => 'US',
        ])->assertStatus(200);

        Queue::assertPushedOn('catalog-sync', SyncLaboratoryToMongoJob::class, function (SyncLaboratoryToMongoJob $job) {
            $doc = (fn () => $this->document)->call($job);
            return $doc['name'] === 'Lab Actualizado'
                && $doc['country_code'] === 'US';
        });
    }

    /** @test @group integration */
    public function deactivating_a_laboratory_via_api_dispatches_sync_job_with_is_active_false(): void
    {
        Queue::fake();

        $create = $this->postJson('/api/v1/catalog/laboratories', [
            'name'         => 'Lab Para Desactivar',
            'country_code' => 'MX',
        ]);
        $create->assertStatus(201);
        $id = $create->json('data.id');

        Queue::fake();

        $this->deleteJson("/api/v1/catalog/laboratories/{$id}")->assertStatus(200);

        Queue::assertPushedOn('catalog-sync', SyncLaboratoryToMongoJob::class, function (SyncLaboratoryToMongoJob $job) {
            $doc = (fn () => $this->document)->call($job);
            return $doc['is_active'] === false;
        });
    }

    /** @test @group integration */
    public function sync_job_document_matches_laboratory_dto_fields(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/catalog/laboratories', [
            'name'         => 'Lab DTO Match',
            'country_code' => 'MX',
            'website'      => 'https://lab.example.com',
        ]);
        $response->assertStatus(201);

        Queue::assertPushedOn('catalog-sync', SyncLaboratoryToMongoJob::class, function (SyncLaboratoryToMongoJob $job) {
            $doc = (fn () => $this->document)->call($job);

            return array_key_exists('id', $doc)
                && array_key_exists('name', $doc)
                && array_key_exists('country_code', $doc)
                && array_key_exists('website', $doc)
                && array_key_exists('is_active', $doc)
                && array_key_exists('created_by', $doc)
                && array_key_exists('created_at', $doc)
                && array_key_exists('updated_at', $doc)
                && $doc['website'] === 'https://lab.example.com';
        });
    }
}
