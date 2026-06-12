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
        Schema::create('routes_of_administration', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 100)->notNull();
            $table->string('code', 10)->notNull();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index('is_active');
        });

        DB::statement('CREATE UNIQUE INDEX routes_of_administration_name_lower_unique ON routes_of_administration (LOWER(name))');
        DB::statement('CREATE UNIQUE INDEX routes_of_administration_code_lower_unique ON routes_of_administration (LOWER(code))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS routes_of_administration_name_lower_unique');
        DB::statement('DROP INDEX IF EXISTS routes_of_administration_code_lower_unique');
        Schema::dropIfExists('routes_of_administration');
    }
};
