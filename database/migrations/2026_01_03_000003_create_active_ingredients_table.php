<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_ingredients', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 150)->notNull();
            $table->string('dci_code', 30)->notNull();
            $table->string('cas_number', 15)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index('is_active');
        });

        DB::statement('CREATE UNIQUE INDEX active_ingredients_name_lower_unique
            ON active_ingredients (LOWER(name))');

        DB::statement('CREATE UNIQUE INDEX active_ingredients_dci_code_lower_unique
            ON active_ingredients (LOWER(dci_code))');

        DB::statement('CREATE UNIQUE INDEX active_ingredients_cas_number_unique
            ON active_ingredients (cas_number) WHERE cas_number IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS active_ingredients_name_lower_unique');
        DB::statement('DROP INDEX IF EXISTS active_ingredients_dci_code_lower_unique');
        DB::statement('DROP INDEX IF EXISTS active_ingredients_cas_number_unique');
        Schema::dropIfExists('active_ingredients');
    }
};
