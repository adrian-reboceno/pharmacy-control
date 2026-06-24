<?php

declare(strict_types=1);

namespace App\Jobs\CatalogSync;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PharmaControl\Shared\Infrastructure\Mongo\MongoCatalogSyncService;

final class SyncRouteToMongoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 60, 300, 900];

    /**
     * @param array{
     *   id:          string,
     *   name:        string,
     *   code:        string,
     *   description: ?string,
     *   is_active:   bool,
     *   created_by:  ?string,
     *   created_at:  string,
     *   updated_at:  string,
     * } $document
     */
    public function __construct(
        private readonly array $document,
    ) {
        $this->onQueue('catalog-sync');
    }

    public function handle(MongoCatalogSyncService $mongo): void
    {
        $mongo->upsert('routes_of_administration', $this->document['id'], $this->document);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncRouteToMongoJob falló permanentemente', [
            'route_id' => $this->document['id'],
            'error' => $exception->getMessage(),
        ]);
    }
}
