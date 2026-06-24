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

final class SyncLaboratoryToMongoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int> Backoff exponencial en segundos entre reintentos */
    public array $backoff = [5, 15, 60, 300, 900];

    /**
     * @param array{
     *   id:           string,
     *   name:         string,
     *   country_code: string,
     *   website:      ?string,
     *   is_active:    bool,
     *   created_by:   ?string,
     *   created_at:   string,
     *   updated_at:   string,
     * } $document
     */
    public function __construct(
        private readonly array $document,
    ) {
        // FIX: la propiedad pública $queue colisionaba con la definición
        // interna del trait Queueable (causaba FatalError de composición
        // de propiedades incompatibles). La forma correcta de asignar la
        // cola es llamando a onQueue() en el constructor, no declarando
        // una propiedad con el mismo nombre.
        $this->onQueue('catalog-sync');
    }

    public function handle(MongoCatalogSyncService $mongo): void
    {
        $mongo->upsert('laboratories', $this->document['id'], $this->document);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncLaboratoryToMongoJob falló permanentemente', [
            'laboratory_id' => $this->document['id'],
            'error' => $exception->getMessage(),
        ]);
    }
}
