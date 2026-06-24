<?php

declare(strict_types=1);

namespace Tests\Unit\App\Jobs\CatalogSync;

use App\Jobs\CatalogSync\SyncLaboratoryToMongoJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PharmaControl\Shared\Infrastructure\Mongo\MongoCatalogSyncService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SyncLaboratoryToMongoJobTest extends TestCase
{
    private array $document = [
        'id' => '550e8400-e29b-41d4-a716-446655440000',
        'name' => 'Laboratorio Test',
        'country_code' => 'MX',
        'website' => null,
        'is_active' => true,
        'created_by' => null,
        'created_at' => '2026-01-01T00:00:00+00:00',
        'updated_at' => '2026-01-01T00:00:00+00:00',
    ];

    /** @test */
    public function it_calls_mongo_upsert_with_correct_collection_and_document(): void
    {
        /** @var MongoCatalogSyncService&MockObject $mongo */
        $mongo = $this->createMock(MongoCatalogSyncService::class);

        $mongo->expects($this->once())
            ->method('upsert')
            ->with(
                'laboratories',
                '550e8400-e29b-41d4-a716-446655440000',
                $this->document,
            );

        $job = new SyncLaboratoryToMongoJob($this->document);
        $job->handle($mongo);
    }

    /** @test */
    public function it_is_queued_on_catalog_sync_queue(): void
    {
        Queue::fake();

        SyncLaboratoryToMongoJob::dispatch($this->document);

        Queue::assertPushedOn('catalog-sync', SyncLaboratoryToMongoJob::class);
    }

    /** @test */
    public function it_has_five_tries_configured(): void
    {
        $job = new SyncLaboratoryToMongoJob($this->document);

        $this->assertSame(5, $job->tries);
    }

    /** @test */
    public function it_has_exponential_backoff_configured(): void
    {
        $job = new SyncLaboratoryToMongoJob($this->document);

        $this->assertSame([5, 15, 60, 300, 900], $job->backoff);
    }

    /** @test */
    public function it_logs_error_when_job_fails_permanently(): void
    {
        Log::spy();

        $job = new SyncLaboratoryToMongoJob($this->document);
        $exception = new \RuntimeException('Conexión MongoDB rechazada');

        $job->failed($exception);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('SyncLaboratoryToMongoJob falló permanentemente', [
                'laboratory_id' => '550e8400-e29b-41d4-a716-446655440000',
                'error' => 'Conexión MongoDB rechazada',
            ]);
    }
}
