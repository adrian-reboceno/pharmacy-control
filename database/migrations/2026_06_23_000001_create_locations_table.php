<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('name', 100)->notNull();
            $table->unsignedTinyInteger('level')->notNull();
            $table->uuid('parent_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestampsTz();

            $table->index('parent_id');
            $table->index('level');
            $table->index('is_active');
        });

        // FK self-referencing en Schema::table separado — después de que la tabla existe con su PK
        Schema::table('locations', function (Blueprint $table): void {
            $table->foreign('parent_id')
                ->references('id')
                ->on('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('locations');
    }
};
