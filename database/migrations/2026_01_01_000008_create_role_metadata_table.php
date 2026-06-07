<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

// Extiende roles de Spatie con metadatos de jerarquía y scope sin tocar su schema.
// Relación 1:1 con roles — role_id es la PK (no hay id UUID separado).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_metadata', function (Blueprint $table): void {
            $table->uuid('role_id')->primary();
            $table->string('display_name', 100);
            $table->smallInteger('hierarchy_level');
            $table->boolean('branch_scoped');
            $table->timestampsTz();

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE role_metadata ADD CONSTRAINT role_metadata_hierarchy_level_check
            CHECK (hierarchy_level BETWEEN 1 AND 10)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE role_metadata DROP CONSTRAINT IF EXISTS role_metadata_hierarchy_level_check');
        Schema::dropIfExists('role_metadata');
    }
};
