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
        Schema::create('units_of_measurement', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('name', 80)->notNull();
            $table->string('symbol', 20)->unique()->notNull();
            $table->string('type', 20)->notNull();
            $table->boolean('is_active')->default(true);

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestampsTz();

            $table->index('type');
            $table->index('is_active');
        });

        DB::statement("ALTER TABLE units_of_measurement
            ADD CONSTRAINT units_of_measurement_type_check
            CHECK (type IN ('QUANTITY','CONCENTRATION'))");

        DB::statement('CREATE UNIQUE INDEX units_of_measurement_name_lower_unique
            ON units_of_measurement (LOWER(name))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS units_of_measurement_name_lower_unique');
        DB::statement('ALTER TABLE units_of_measurement
            DROP CONSTRAINT IF EXISTS units_of_measurement_type_check');
        Schema::dropIfExists('units_of_measurement');
    }
};
