<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Migración separada para aplicar/revertir GIN independientemente del schema base.
// Optimiza búsquedas JSONB requeridas por reportes COFEPRIS.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE INDEX audit_logs_old_values_gin
            ON audit_logs USING GIN (old_values)
            WHERE old_values IS NOT NULL");

        DB::statement("CREATE INDEX audit_logs_new_values_gin
            ON audit_logs USING GIN (new_values)
            WHERE new_values IS NOT NULL");

        DB::statement("CREATE INDEX audit_logs_metadata_gin
            ON audit_logs USING GIN (metadata)
            WHERE metadata IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS audit_logs_old_values_gin');
        DB::statement('DROP INDEX IF EXISTS audit_logs_new_values_gin');
        DB::statement('DROP INDEX IF EXISTS audit_logs_metadata_gin');
    }
};
