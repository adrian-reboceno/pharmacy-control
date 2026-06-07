<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

// Restricciones Static Separation of Duty (SSoD / RBAC2).
// La exclusión es bidireccional; la BD almacena UNA sola fila por par.
// La validación en código debe buscar en ambas direcciones (role_a↔role_b).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_exclusions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('role_a_id');
            $table->uuid('role_b_id');
            $table->string('level', 20);
            $table->text('reason');
            $table->uuid('created_by');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('role_a_id')->references('id')->on('roles')->restrictOnDelete();
            $table->foreign('role_b_id')->references('id')->on('roles')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            // Unicidad del par — la fila siempre se inserta con role_a < role_b léxicamente
            $table->unique(['role_a_id', 'role_b_id']);

            // role_a_id ya queda indexada por la FK; role_b_id necesita índice para búsqueda inversa
            $table->index('role_b_id');
        });

        DB::statement("ALTER TABLE role_exclusions ADD CONSTRAINT role_exclusions_role_ids_check
            CHECK (role_a_id <> role_b_id)");

        DB::statement("ALTER TABLE role_exclusions ADD CONSTRAINT role_exclusions_level_check
            CHECK (level IN ('ABSOLUTE','RECOMMENDED'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE role_exclusions DROP CONSTRAINT IF EXISTS role_exclusions_role_ids_check');
        DB::statement('ALTER TABLE role_exclusions DROP CONSTRAINT IF EXISTS role_exclusions_level_check');
        Schema::dropIfExists('role_exclusions');
    }
};
