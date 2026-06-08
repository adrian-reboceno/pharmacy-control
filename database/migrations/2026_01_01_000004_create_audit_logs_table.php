<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// TABLA APPEND-ONLY — el rol de BD de la app NO debe tener permisos UPDATE/DELETE.
// Retención mínima 2 años (requisito regulatorio COFEPRIS).
// user_id sin FK por diseño: el log persiste aunque el usuario sea eliminado.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id'); // SIN FK — denormalizado, historial independiente del usuario
            $table->string('user_email', 255);
            $table->string('user_role', 100);
            $table->uuid('branch_id')->nullable()->comment('NULL para acciones globales sin sucursal');
            $table->string('module', 50);
            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable()->comment('NULL en acciones sin entidad (LOGIN)');
            $table->uuid('entity_id')->nullable()->comment('NULL mismo criterio que entity_type');
            $table->jsonb('old_values')->nullable()->comment('NULL; solo en UPDATE — estado anterior');
            $table->jsonb('new_values')->nullable()->comment('NULL; CREATE/UPDATE — estado posterior');
            $table->jsonb('metadata')->nullable()->comment('NULL; contexto adicional: motivo, referencia externa');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable()->comment('NULL: algunos clientes no lo envían');
            $table->string('status', 10);
            $table->timestampTz('timestamp')->useCurrent();

            $table->index('user_id');
            $table->index(['module', 'action']);
            $table->index('timestamp');
        });

        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_status_check
            CHECK (status IN ('SUCCESS','FAILED','BLOCKED'))");

        DB::statement('CREATE INDEX audit_logs_entity_id_partial_idx
            ON audit_logs (entity_id)
            WHERE entity_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS audit_logs_entity_id_partial_idx');
        DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_status_check');
        Schema::dropIfExists('audit_logs');
    }
};
