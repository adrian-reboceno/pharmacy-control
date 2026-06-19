<?php

declare(strict_types=1);

namespace PharmaControl\Shared\Infrastructure\Mongo;

use Illuminate\Support\Facades\Log;
use MongoDB\Client as MongoClient;

final class MongoCatalogSyncService
{
    private readonly MongoClient $client;

    public function __construct()
    {
        $this->client = new MongoClient(config('database.connections.mongodb.dsn'));
    }

    /**
     * Inserta o actualiza un documento en la colección indicada.
     * Operación idempotente — se puede reintentar sin efectos secundarios.
     *
     * @param string               $collection Nombre de la colección Mongo (ej. 'laboratories')
     * @param string               $id         UUID del documento (mismo que en Postgres)
     * @param array<string, mixed> $document   Documento completo a almacenar
     */
    public function upsert(string $collection, string $id, array $document): void
    {
        $db = $this->client->selectDatabase(config('database.connections.mongodb.database'));

        $db->selectCollection($collection)->updateOne(
            ['_id' => $id],
            ['$set' => array_merge($document, ['_id' => $id])],
            ['upsert' => true],
        );
    }

    /**
     * Elimina físicamente un documento de Mongo.
     * Solo para el comando de resync — la sincronización incremental usa soft delete (is_active=false).
     */
    public function delete(string $collection, string $id): void
    {
        $db = $this->client->selectDatabase(config('database.connections.mongodb.database'));
        $db->selectCollection($collection)->deleteOne(['_id' => $id]);
    }

    /**
     * Vacía una colección completa. Solo para el comando de resync con --truncate.
     */
    public function truncate(string $collection): void
    {
        $db = $this->client->selectDatabase(config('database.connections.mongodb.database'));
        $db->selectCollection($collection)->deleteMany([]);
    }

    /**
     * Crea índices en los campos indicados. Llamado una vez desde el comando de resync.
     *
     * @param string[] $fields
     */
    public function ensureIndexes(string $collection, array $fields): void
    {
        $db = $this->client->selectDatabase(config('database.connections.mongodb.database'));
        $coll = $db->selectCollection($collection);

        foreach ($fields as $field) {
            try {
                $coll->createIndex([$field => 1]);
            } catch (\Throwable $e) {
                Log::warning("No se pudo crear índice {$field} en {$collection}: {$e->getMessage()}");
            }
        }
    }
}
