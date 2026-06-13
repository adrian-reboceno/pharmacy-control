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
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('type', 10)->notNull();

            $table->string('rfc', 13)->nullable();

            $table->string('legal_name', 200)->notNull();
            $table->string('trade_name', 150)->nullable();

            $table->string('address_street', 150)->notNull();
            $table->string('address_ext_number', 20)->notNull();
            $table->string('address_int_number', 20)->nullable();
            $table->string('address_neighborhood', 100)->notNull();
            $table->string('address_municipality', 100)->notNull();
            $table->string('address_state', 50)->notNull();
            $table->string('address_postal_code', 5)->notNull();
            $table->string('address_country', 2)->default('MX');

            $table->string('phone', 10)->nullable();
            $table->string('email', 255)->nullable();

            $table->boolean('is_active')->default(true);

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestampsTz();

            $table->index('is_active');
            $table->index('type');
        });

        DB::statement("ALTER TABLE suppliers
            ADD CONSTRAINT suppliers_type_check
            CHECK (type IN ('MORAL','FISICA'))");

        DB::statement("ALTER TABLE suppliers
            ADD CONSTRAINT suppliers_postal_code_check
            CHECK (address_postal_code ~ '^[0-9]{5}$')");

        DB::statement('CREATE UNIQUE INDEX suppliers_rfc_unique
            ON suppliers (rfc) WHERE rfc IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS suppliers_rfc_unique');
        DB::statement('ALTER TABLE suppliers DROP CONSTRAINT IF EXISTS suppliers_postal_code_check');
        DB::statement('ALTER TABLE suppliers DROP CONSTRAINT IF EXISTS suppliers_type_check');
        Schema::dropIfExists('suppliers');
    }
};
