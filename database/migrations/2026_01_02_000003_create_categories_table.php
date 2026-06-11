<?php

// ── ARCHIVO: database/migrations/2026_01_02_000003_create_categories_table.php ──
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();  // ← primary() ANTES que cualquier FK

            $table->uuid('parent_id')->nullable();

            $table->string('name', 120)->notNull();
            $table->string('slug', 160)->unique()->notNull();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestampsTz();

            $table->index('parent_id');
            $table->index('is_active');
        });

        // FK self-referencing en Schema::table SEPARADO
        // — después de que la tabla ya existe con su PK definida
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('categories');
    }
};
