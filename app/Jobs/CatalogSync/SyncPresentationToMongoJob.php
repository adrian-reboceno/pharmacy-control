<?php
declare(strict_types=1);

namespace App\Jobs\CatalogSync;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PharmaControl\Shared\Infrastructure\Mongo\MongoCatalogSyncService;

final class SyncPresentationToMongoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int> Backoff exponencial en segundos entre reintentos */
    public array $backoff = [5, 15, 60, 300, 900];

    /**
     * @param array{
     *   id:           string,
     *   name:         string,
     *   abbreviation: string,
     *   description:  ?string,
     *   is_active:    bool,
     *   created_by:   ?string,
     *   created_at:   string,
     *   updated_at:   string,
     * } $document
     */
    public function __construct(
        private readonly array $document,
    ) {
        $this->onQueue('catalog-sync');
    }

    public function handle(MongoCatalogSyncService $mongo): void
    {
        $mongo->upsert('presentations', $this->document['id'], $this->document);
    }

    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('SyncPresentationToMongoJob falló permanentemente', [
            'presentation_id' => $this->document['id'],
            'error'           => $exception->getMessage(),
        ]);
    }
}