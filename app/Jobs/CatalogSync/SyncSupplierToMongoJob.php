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

final class SyncSupplierToMongoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 60, 300, 900];

    /**
     * @param array{
     *   id:         string,
     *   type:       string,
     *   type_label: string,
     *   rfc:        ?string,
     *   legal_name: string,
     *   trade_name: ?string,
     *   address: array{
     *     street:       string,
     *     ext_number:   string,
     *     int_number:   ?string,
     *     neighborhood: string,
     *     municipality: string,
     *     state:        string,
     *     postal_code:  string,
     *     country:      string,
     *   },
     *   phone:      ?string,
     *   email:      ?string,
     *   is_active:  bool,
     *   created_by: ?string,
     *   created_at: string,
     *   updated_at: string,
     * } $document
     */
    public function __construct(
        private readonly array $document,
    ) {
        // FIX: public string $queue colisionaba con la definición interna
        // del trait Queueable (FatalError de composición de propiedades
        // incompatibles). Se asigna la cola vía onQueue() en su lugar.
        $this->onQueue('catalog-sync');
    }

    public function handle(MongoCatalogSyncService $mongo): void
    {
        $mongo->upsert('suppliers', $this->document['id'], $this->document);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncSupplierToMongoJob falló permanentemente', [
            'supplier_id' => $this->document['id'],
            'error' => $exception->getMessage(),
        ]);
    }
}
