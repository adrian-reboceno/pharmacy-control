<?php

// ── ARCHIVO: database/migrations/2026_01_02_000002_create_medication_classifications_table.php ──
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_classifications', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('lgs_group', 10)->notNull();
            $table->string('name', 100)->notNull();
            $table->string('prescription_type', 30)->notNull();
            $table->unsignedSmallInteger('validity_days')->nullable();
            $table->string('validity_note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique('lgs_group');
            $table->index('is_active');
        });

        DB::statement("ALTER TABLE medication_classifications
            ADD CONSTRAINT medication_classifications_lgs_group_check
            CHECK (lgs_group IN ('I','II','III','IV_A','IV_B','V','VI'))");

        DB::statement("ALTER TABLE medication_classifications
            ADD CONSTRAINT medication_classifications_prescription_type_check
            CHECK (prescription_type IN ('CON_CODIGO_BARRAS','NORMAL','SIN_RECETA'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE medication_classifications
            DROP CONSTRAINT IF EXISTS medication_classifications_lgs_group_check');
        DB::statement('ALTER TABLE medication_classifications
            DROP CONSTRAINT IF EXISTS medication_classifications_prescription_type_check');
        Schema::dropIfExists('medication_classifications');
    }
};
