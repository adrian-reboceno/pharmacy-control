<?php

// ── ARCHIVO: database/migrations/2026_01_02_000001_create_laboratories_table.php ──
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratories', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 120)->unique();
            $table->string('country_code', 2)->notNull();
            $table->text('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('is_active');
            $table->index('country_code');
        });

        DB::statement(
            "ALTER TABLE laboratories ADD CONSTRAINT laboratories_country_code_check
             CHECK (country_code ~ '^[A-Z]{2}$')"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE laboratories DROP CONSTRAINT IF EXISTS laboratories_country_code_check'
        );
        Schema::dropIfExists('laboratories');
    }
};
