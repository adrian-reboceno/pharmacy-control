<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

// Extiende la tabla de Spatie model_has_roles con columnas de scope de sucursal
// y trazabilidad de asignación. NO usar after() — PostgreSQL no lo soporta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->uuid('branch_id')->nullable()->comment('NULL = rol global (super-admin, auditor); UUID = scoped a sucursal');
            $table->uuid('assigned_by')->nullable()->comment('NULL solo para roles del seeder inicial');
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable()->comment('NULL = sin expiración; con valor = rol temporal');

            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement("CREATE INDEX model_has_roles_branch_id_idx
            ON model_has_roles (branch_id)
            WHERE branch_id IS NOT NULL");

        DB::statement("CREATE INDEX model_has_roles_expires_at_idx
            ON model_has_roles (expires_at)
            WHERE expires_at IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS model_has_roles_branch_id_idx');
        DB::statement('DROP INDEX IF EXISTS model_has_roles_expires_at_idx');

        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['branch_id', 'assigned_by', 'assigned_at', 'expires_at']);
        });
    }
};
